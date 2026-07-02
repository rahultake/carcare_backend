<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Get user's order history
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $orders = Order::with(['items.product.images', 'payment'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($order) {
                return $this->transformOrder($order);
            });

        return response()->json([
            'status' => 'success',
            'data' => [
                'orders' => $orders
            ]
        ]);
    }

    /**
     * Get single order details
     */
    public function show($id, Request $request)
    {
        $user = $request->user();

        $order = Order::with(['items.product.images', 'payment'])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'order' => []
                ]
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'order' => $this->transformOrder($order, true)
            ]
        ]);
    }

    /**
     * Cancel order (user-side: works if unpaid, OR if paid but not yet shipped)
     */
    public function cancel($id, Request $request)
    {
        $user = $request->user();

        $order = Order::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found'
            ], 404);
        }

        if ($order->status === 'cancelled') {
            return response()->json([
                'status' => 'error',
                'message' => 'Order is already cancelled'
            ], 400);
        }

        // Paid orders can only be cancelled if they have not been shipped out yet
        if ($order->payment_status === 'completed' && in_array($order->shipping_status, ['shipped', 'delivered', 'rto'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot cancel order after it has been shipped. Please request a return after delivery.'
            ], 400);
        }

        // If the order has already been booked with carrier, cancel it on carrier side
        if ($order->awb_code) {
            $shipmentData = json_decode($order->shipment_data, true) ?: [];
            $provider = $shipmentData['provider'] ?? 'shiprocket';

            if ($provider === 'parcelx') {
                try {
                    $parcelx = new \App\Services\ParcelXService();
                    $parcelx->cancelOrder($order->awb_code);
                } catch (\Exception $e) {
                    Log::error("Failed to cancel ParcelX order: " . $e->getMessage());
                }
            } else {
                try {
                    $shiprocket = new \App\Services\ShiprocketService();
                    if ($order->shiprocket_order_id) {
                        $shiprocket->cancelOrder($order->shiprocket_order_id);
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to cancel Shiprocket order: " . $e->getMessage());
                }
            }
        }

        // Restock inventory
        foreach ($order->items as $item) {
            if ($item->product && $item->product->track_inventory) {
                $item->product->increment('quantity', $item->quantity);
            }
        }

        // Handle payment gateway refund if paid via Razorpay
        $refunded = false;
        if ($order->payment_status === 'completed' && $order->total_amount > 0) {
            $refundResult = \App\Http\Controllers\Api\PaymentController::initiateRazorpayRefund(
                $order,
                $order->total_amount,
                'Customer cancellation via app'
            );
            if ($refundResult && $refundResult['status'] === 'success') {
                $refunded = true;
            }
        }

        // Update database statuses
        $order->update([
            'status' => 'cancelled',
            'payment_status' => $refunded ? 'refunded' : ($order->payment_status === 'completed' ? 'refunded' : 'failed'),
            'shipping_status' => 'cancelled',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => $refunded 
                ? 'Order cancelled and refund initiated successfully.' 
                : 'Order cancelled successfully.'
        ]);
    }

    /**
     * Request return / RMA for delivered items
     */
    public function requestReturn($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:255',
            'items' => 'required|array',
            'items.*.order_item_id' => 'required|exists:order_items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $order = Order::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found'
            ], 404);
        }

        // Can only return delivered orders
        if ($order->shipping_status !== 'delivered' && $order->status !== 'completed') {
            return response()->json([
                'status' => 'error',
                'message' => 'Can only return delivered orders.'
            ], 400);
        }

        // Return window validation (e.g. 7 days from delivery date)
        $deliveryDate = $order->delivered_at ?: $order->updated_at;
        if (now()->diffInDays($deliveryDate) > 7) {
            return response()->json([
                'status' => 'error',
                'message' => 'Return window has expired. Returns are only allowed within 7 days of delivery.'
            ], 400);
        }

        // Calculate refund amount and validate return items exist in this order
        $refundAmount = 0.00;
        $returnItems = [];

        foreach ($request->items as $reqItem) {
            $orderItem = $order->items()->where('id', $reqItem['order_item_id'])->first();
            if (!$orderItem) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Item does not belong to this order.'
                ], 400);
            }

            if ($reqItem['quantity'] > $orderItem->quantity) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Cannot return more quantity than purchased for '{$orderItem->product_name}'."
                ], 400);
            }

            $refundAmount += $orderItem->price * $reqItem['quantity'];
            $returnItems[] = [
                'order_item_id' => $orderItem->id,
                'product_name' => $orderItem->product_name,
                'product_sku' => $orderItem->product_sku,
                'quantity' => $reqItem['quantity'],
                'price' => $orderItem->price,
            ];
        }

        // Create return request entry
        $returnRequest = \App\Models\ReturnRequest::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'reason' => $request->reason,
            'status' => 'pending',
            'items' => $returnItems,
            'refund_amount' => $refundAmount,
        ]);

        // Update overall order status
        $order->update([
            'status' => 'return_requested'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Return request submitted successfully. A courier pickup will be scheduled upon approval.',
            'data' => [
                'return_request_id' => $returnRequest->id,
                'refund_amount' => $refundAmount
            ]
        ]);
    }

    public function orderHistory(Request $request)
    {
        $user = $request->user();

        $orders = Order::with(['items.product.images'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'DESC')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'orders' => $orders->map(function($order) {
                    return $this->transformOrder($order, true); // list version
                })
            ]
        ]);
    }

    /**
     * Transform order data for API response
     */
    private function transformOrder($order, $detailed = false)
    {
        $data = [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'subtotal' => (float) $order->subtotal,
            'discount' => (float) $order->discount,
            'shipping_cost' => (float) $order->shipping_cost,
            'tax' => (float) $order->tax,
            'cgst_amount' => (float) ($order->cgst_amount ?? 0),
            'sgst_amount' => (float) ($order->sgst_amount ?? 0),
            'igst_amount' => (float) ($order->igst_amount ?? 0),
            'total_amount' => (float) $order->total_amount,
            'items_count' => $order->items->count(),
            'ordered_at' => $order->ordered_at?->toISOString(),
            'paid_at' => $order->paid_at?->toISOString(),
            'created_at' => $order->created_at->toISOString(),
        ];

        if ($detailed) {
            $shippingAddr = json_decode($order->shipping_address, true);
            $billingAddr  = json_decode($order->billing_address, true);

            $data['shipping_address']  = $shippingAddr;
            $data['billing_address']   = $billingAddr;
            $data['company_name']      = $shippingAddr['company_name'] ?? null;
            $data['gstin_number']      = $shippingAddr['gstin_number'] ?? null;
            $data['shipping_status']   = $order->shipping_status;
            $data['tracking_number'] = $order->tracking_number;
            $data['shipping_provider'] = $order->shipping_provider;
            $data['coupon_code'] = $order->coupon_code;
            $data['razorpay_order_id'] = $order->razorpay_order_id;
            $data['razorpay_payment_id'] = $order->razorpay_payment_id;
            $data['shiprocket_order_id'] = $order->shiprocket_order_id;
            $data['shiprocket_shipment_id'] = $order->shiprocket_shipment_id;
            $data['awb_code'] = $order->awb_code;
            $data['shipment_data'] = json_decode($order->shipment_data, true);
            
            // Dynamic customer tracking URL (Shopify standard) & Expected Delivery Date
            $trackingUrl = null;
            $expectedDeliveryDate = null;
            if ($order->awb_code) {
                $shipmentData = json_decode($order->shipment_data, true) ?: [];
                $provider = $shipmentData['provider'] ?? 'shiprocket';
                
                $trackingUrl = ($provider === 'parcelx') 
                    ? "https://app.parcelx.in/track?awb={$order->awb_code}" 
                    : "https://shiprocket.co/tracking/{$order->awb_code}";
                
                if ($provider === 'parcelx') {
                    $expectedDeliveryDate = $shipmentData['latest_carrier_track']['expected_delivery_date'] 
                        ?? $shipmentData['latest_carrier_track']['edd'] 
                        ?? null;
                } else {
                    $expectedDeliveryDate = $shipmentData['latest_carrier_track']['edd'] ?? null;
                }
            }
            $data['tracking_url'] = $trackingUrl;
            $data['expected_delivery_date'] = $expectedDeliveryDate;
            
            $data['items'] = $order->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'product_sku' => $item->product_sku,
                    'quantity' => $item->quantity,
                    'price' => (float) $item->price,
                    'subtotal' => (float) $item->subtotal,
                    'cgst_percent' => (float) ($item->cgst_percent ?? 0),
                    'sgst_percent' => (float) ($item->sgst_percent ?? 0),
                    'igst_percent' => (float) ($item->igst_percent ?? 0),
                    'cgst_amount' => (float) ($item->cgst_amount ?? 0),
                    'sgst_amount' => (float) ($item->sgst_amount ?? 0),
                    'igst_amount' => (float) ($item->igst_amount ?? 0),
                    'tax_amount' => (float) ($item->tax_amount ?? 0),
                    'product_options' => $item->product_options,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                    'product' => $item->product ? [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'slug' => $item->product->slug,
                        'primary_image' => $item->product->images->where('is_primary', true)->first() 
                            ? asset('storage/' . $item->product->images->where('is_primary', true)->first()->image_path)
                            : ($item->product->images->first() ? asset('storage/' . $item->product->images->first()->image_path) : null),
                    ] : null,
                ];
            });
        } else {
            // For list view, show limited item info
            $data['items'] = $order->items->take(3)->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'price' => (float) $item->price,
                    'subtotal' => (float) $item->subtotal,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                    'product' => $item->product ? [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'slug' => $item->product->slug,
                        'primary_image' => $item->product->images->where('is_primary', true)->first() 
                            ? asset('storage/' . $item->product->images->where('is_primary', true)->first()->image_path)
                            : ($item->product->images->first() ? asset('storage/' . $item->product->images->first()->image_path) : null),
                    ] : null,
                ];
            }); 
        }

        return $data;
    }
}