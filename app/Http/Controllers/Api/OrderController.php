<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;

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
     * Cancel order (only if payment is pending)
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

        if ($order->payment_status === 'completed') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot cancel paid order. Please contact support for refund.'
            ], 400);
        }

        if ($order->status === 'cancelled') {
            return response()->json([
                'status' => 'error',
                'message' => 'Order is already cancelled'
            ], 400);
        }

        $order->update([
            'status' => 'cancelled',
            'payment_status' => 'failed',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Order cancelled successfully'
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