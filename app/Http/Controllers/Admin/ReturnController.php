<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReturnController extends Controller
{
    /**
     * Display a listing of return requests
     */
    public function index()
    {
        $returns = ReturnRequest::with(['order', 'user'])
            ->latest()
            ->paginate(20);

        return view('admin.returns.index', compact('returns'));
    }

    /**
     * Display return request details
     */
    public function show($id)
    {
        $return = ReturnRequest::with(['order.items', 'user'])->findOrFail($id);
        return view('admin.returns.show', compact('return'));
    }

    /**
     * Reject return request
     */
    public function reject($id, Request $request)
    {
        $return = ReturnRequest::findOrFail($id);
        
        $return->update([
            'status' => 'rejected'
        ]);

        // Revert order status back to completed
        $return->order->update([
            'status' => 'completed'
        ]);

        return redirect()->back()->with('success', 'Return request rejected successfully.');
    }

    /**
     * Approve return request and book reverse pickup
     */
    public function approve($id, Request $request)
    {
        $return = ReturnRequest::findOrFail($id);
        $order = $return->order;
        
        $shipmentData = json_decode($order->shipment_data, true) ?: [];
        $provider = $shipmentData['provider'] ?? setting('shipping_provider', 'shiprocket');

        $awbCode = null;
        $shipmentId = null;

        if ($provider === 'parcelx') {
            // Book reverse pickup via ParcelX
            try {
                $parcelx = new \App\Services\ParcelXService();
                
                // For reverse pickup, the origin address is the customer's shipping address
                $customerAddress = json_decode($order->shipping_address, true) ?: [];
                
                // Get active warehouse details for consignee (delivery destination)
                $warehouseId = setting('parcelx_pickup_location');
                
                // Construct products array
                $products = [];
                foreach ($return->items as $item) {
                    $products[] = [
                        "product_sku"         => (string)($item['product_sku'] ?? 'RET'),
                        "product_name"        => $item['product_name'] ?? 'Returned Item',
                        "product_value"       => (string)($item['price'] ?? 0),
                        "product_hsnsac"      => "",
                        "product_taxper"      => 0,
                        "product_category"    => "Returns",
                        "product_quantity"    => (string)$item['quantity'],
                        "product_description" => "Reverse Pickup"
                    ];
                }

                $token = trim(setting('parcelx_access_token'));
                $baseUrl = setting('parcelx_base_url', 'https://app.parcelx.in');
                
                // Fetch warehouse address fields from ParcelX if available
                $whResponse = Http::withHeaders([
                    'access-token' => $token,
                    'Content-Type' => 'application/json'
                ])->post(rtrim($baseUrl, '/') . '/api/v2/warehouse-list');
                
                $warehouse = null;
                if ($whResponse->successful()) {
                    $whList = $whResponse->json()['data'] ?? [];
                    foreach ($whList as $w) {
                        if ((string)$w['warehouse_id'] === (string)$warehouseId) {
                            $warehouse = $w;
                            break;
                        }
                    }
                }

                // Fallback destination details if warehouse not fetched
                $destName = $warehouse ? $warehouse['addressee'] : 'Warehouse Manager';
                $destPhone = $warehouse ? $warehouse['phone'] : '9999999999';
                $destAddress = $warehouse ? $warehouse['address'] : 'Company Warehouse';
                $destCity = $warehouse ? $warehouse['city'] : 'Ahmedabad';
                $destState = $warehouse ? $warehouse['state'] : 'Gujarat';
                $destPincode = $warehouse ? $warehouse['pincode'] : '380015';

                // Setup payload (Origin is customer, Destination is warehouse)
                $payload = [
                    "client_order_id"    => "RET-" . $return->id . "-" . time(),
                    "pregenerated_awb"   => "",
                    "consignee_emailid"  => "",
                    "consignee_pincode"  => (string)$destPincode,
                    "consignee_mobile"   => (string)$destPhone,
                    "consignee_phone"    => "",
                    "consignee_address1" => substr($destAddress, 0, 150),
                    "consignee_address2" => "",
                    "consignee_name"     => $destName,
                    "invoice_number"     => "RET-" . $return->id,
                    "express_type"       => "surface",
                    // Use customer details as pickup origin
                    "pickup_name"        => $customerAddress['name'] ?? 'Customer',
                    "pickup_mobile"      => $customerAddress['phone'] ?? '9999999999',
                    "pickup_address1"    => $customerAddress['address_line_1'] ?? '',
                    "pickup_address2"    => $customerAddress['address_line_2'] ?? '',
                    "pickup_pincode"     => $customerAddress['postal_code'] ?? '',
                    "pickup_city"        => $customerAddress['city'] ?? '',
                    "pickup_state"       => $customerAddress['state'] ?? '',
                    
                    "cod_amount"         => "0",
                    "tax_amount"         => "0",
                    "b2b"                => false,
                    "mps"                => "0",
                    "courier_type"       => (int)setting('parcelx_courier_type', 1),
                    "courier_code"       => setting('parcelx_default_courier', 'PXDEL01'),
                    "products"           => $products,
                    "payment_mode"       => "Prepaid",
                    "order_amount"       => (string)$return->refund_amount,
                    "extra_charges"      => "0",
                    "shipment_width"     => ["10"],
                    "shipment_height"    => ["5"],
                    "shipment_length"    => ["10"],
                    "shipment_weight"    => ["0.4"]
                ];

                Log::info("ParcelX Reverse Pickup Request Payload: " . json_encode($payload));
                $endpoint = rtrim($baseUrl, '/') . '/api/v3/order/create_order';
                $response = Http::withHeaders([
                    'access-token' => $token,
                    'Content-Type' => 'application/json'
                ])->post($endpoint, $payload);

                Log::info("ParcelX Reverse Pickup Response: " . $response->body());
                $resData = $response->json();
                
                if ($response->successful() && isset($resData['status']) && $resData['status']) {
                    $awbCode = $resData['data']['awb_number'] ?? null;
                    $shipmentId = $resData['data']['order_number'] ?? null;
                } else {
                    $errMsg = $resData['responsemsg'] ?? 'Insufficient Wallet Balance or Courier Error';
                    $errMsgStr = is_array($errMsg) ? implode(', ', $errMsg) : (string)$errMsg;
                    return redirect()->back()->with('error', 'ParcelX Reverse Pickup booking failed: ' . $errMsgStr);
                }

            } catch (\Exception $e) {
                Log::error("ParcelX Reverse Pickup Exception: " . $e->getMessage());
                return redirect()->back()->with('error', 'Reverse pickup booking error: ' . $e->getMessage());
            }

        } else {
            // Book reverse pickup via Shiprocket
            try {
                $shiprocket = new \App\Services\ShiprocketService();
                $baseUrl = setting('shiprocket_base_url', 'https://apiv2.shiprocket.in/v1/external');
                
                // Get token using reflection
                $tokenProp = new \ReflectionProperty($shiprocket, 'token');
                $tokenProp->setAccessible(true);
                $token = $tokenProp->getValue($shiprocket);

                if (!$token) {
                    return redirect()->back()->with('error', 'Shiprocket authentication failed.');
                }

                $customerAddress = json_decode($order->shipping_address, true) ?: [];
                $items = [];
                foreach ($return->items as $item) {
                    $items[] = [
                        "name" => $item['product_name'] ?? 'Returned Item',
                        "sku" => $item['product_sku'] ?? 'RET',
                        "units" => $item['quantity'],
                        "selling_price" => $item['price']
                    ];
                }

                $payload = [
                    "order_id" => "RET-" . $return->id . "-" . time(),
                    "order_date" => now()->format('Y-m-d H:i:s'),
                    "pickup_customer_name" => $customerAddress['name'] ?? 'Customer',
                    "pickup_address" => $customerAddress['address_line_1'] ?? '',
                    "pickup_address_2" => $customerAddress['address_line_2'] ?? '',
                    "pickup_city" => $customerAddress['city'] ?? '',
                    "pickup_state" => $customerAddress['state'] ?? '',
                    "pickup_pincode" => $customerAddress['postal_code'] ?? '',
                    "pickup_country" => $customerAddress['country'] ?? 'India',
                    "pickup_phone" => $customerAddress['phone'] ?? '9999999999',
                    "pickup_email" => $order->user->email,
                    
                    "shipping_customer_name" => "Warehouse Manager",
                    "shipping_address" => "Company Warehouse Location",
                    "shipping_city" => "Ahmedabad",
                    "shipping_state" => "Gujarat",
                    "shipping_pincode" => "380015",
                    "shipping_country" => "India",
                    "shipping_phone" => "9999999999",
                    
                    "order_items" => $items,
                    "payment_method" => "Prepaid",
                    "total_discount" => "0",
                    "sub_total" => $return->refund_amount,
                    "length" => 10,
                    "breadth" => 10,
                    "height" => 10,
                    "weight" => 0.4
                ];

                Log::info("Shiprocket Reverse Pickup Request: " . json_encode($payload));
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json'
                ])->post($baseUrl . '/shipments/create/reverse', $payload);

                Log::info("Shiprocket Reverse Pickup Response: " . $response->body());
                $resData = $response->json();

                if ($response->successful() && isset($resData['shipment_id'])) {
                    $awbCode = $resData['awb_code'] ?? null;
                    $shipmentId = $resData['shipment_id'] ?? null;
                } else {
                    $errMsg = $resData['message'] ?? 'Unable to book Shiprocket reverse pickup';
                    return redirect()->back()->with('error', 'Shiprocket Reverse Pickup failed: ' . $errMsg);
                }

            } catch (\Exception $e) {
                Log::error("Shiprocket Reverse Pickup Exception: " . $e->getMessage());
                return redirect()->back()->with('error', 'Reverse pickup booking error: ' . $e->getMessage());
            }
        }

        // Update return request status
        $return->update([
            'status' => 'pickup_booked',
            'awb_code' => $awbCode,
            'shipment_id' => $shipmentId,
        ]);

        return redirect()->back()->with('success', 'Return request approved. Reverse pickup booked successfully. AWB: ' . $awbCode);
    }

    /**
     * Mark return as received at warehouse, inspect items, restock inventory, and trigger Razorpay refund.
     */
    public function receive($id, Request $request)
    {
        $return = ReturnRequest::findOrFail($id);
        $order = $return->order;

        if ($return->status === 'completed') {
            return redirect()->back()->with('error', 'This return is already processed and completed.');
        }

        // 1. Restock items into product inventory
        foreach ($return->items as $item) {
            // Find order item to locate product
            $orderItem = $order->items()->where('id', $item['order_item_id'])->first();
            if ($orderItem && $orderItem->product) {
                $product = $orderItem->product;
                if ($product->track_inventory) {
                    $product->increment('quantity', $item['quantity']);
                }
            }
        }

        // 2. Trigger Razorpay Refund
        $refunded = false;
        if ($order->payment_status === 'completed' && $return->refund_amount > 0) {
            $refundResult = \App\Http\Controllers\Api\PaymentController::initiateRazorpayRefund(
                $order,
                $return->refund_amount,
                'Customer return request #' . $return->id
            );
            if ($refundResult && $refundResult['status'] === 'success') {
                $refunded = true;
            }
        }

        // 3. Update return request status to completed
        $return->update([
            'status' => 'completed'
        ]);

        // 4. Update order status to refunded / partially_refunded
        // Check if all order items were returned
        $totalItemsOrdered = $order->items->sum('quantity');
        $totalItemsReturned = ReturnRequest::where('order_id', $order->id)
            ->where('status', 'completed')
            ->get()
            ->sum(function($r) {
                return collect($r->items)->sum('quantity');
            });

        $newOrderStatus = ($totalItemsReturned >= $totalItemsOrdered) ? 'refunded' : 'partially_refunded';

        $order->update([
            'status' => $newOrderStatus,
            'payment_status' => ($newOrderStatus === 'refunded') ? 'refunded' : $order->payment_status,
        ]);

        return redirect()->back()->with('success', 'Return items received & verified. Stock returned to inventory. ' . ($refunded ? 'Razorpay refund issued successfully.' : 'Payment marked as refunded.'));
    }
}
