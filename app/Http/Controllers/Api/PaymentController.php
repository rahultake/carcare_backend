<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Razorpay\Api\Api;

class PaymentController extends Controller
{
    private $razorpayApi;

    public function __construct()
    {
        $this->razorpayApi = new Api(
            config('razorpay.key_id'),
            config('razorpay.key_secret')
        );
    }

    /**
     * Create Razorpay order before checkout
     */
    public function createOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shipping_address' => 'required|array',
            'shipping_address.name' => 'required|string|max:255',
            'shipping_address.phone' => 'required|string|max:20',
            'shipping_address.address_line_1' => 'required|string|max:255',
            'shipping_address.city' => 'required|string|max:100',
            'shipping_address.state' => 'required|string|max:100',
            'shipping_address.postal_code' => 'required|string|max:20',
            'shipping_address.country' => 'required|string|max:100',
            'shipping_address.company_name' => 'nullable|string|max:255',
            'shipping_address.gstin_number' => 'nullable|string|max:100',
            'coupon_code' => 'nullable|string|exists:coupons,code',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        
        // Get cart items
        $cartItems = Cart::where('user_id', $user->id)
                        ->with('product')
                        ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cart is empty'
            ], 400);
        }

        // Validate stock
        foreach ($cartItems as $item) {
            if ($item->product->status !== 'active') {
                return response()->json([
                    'status' => 'error',
                    'message' => "Product '{$item->product->name}' is no longer available"
                ], 400);
            }

            if ($item->product->track_inventory && $item->product->quantity < $item->quantity) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Insufficient stock for '{$item->product->name}'. Only {$item->product->quantity} available"
                ], 400);
            }
        }

        // Calculate totals
        $subtotal = $cartItems->sum(function ($item) {
            return $item->quantity * $item->price;
        });

        $discount = 0;
        $couponCode = null;

        if ($request->coupon_code) {
            $coupon = \App\Models\Coupon::where('code', $request->coupon_code)
                                    ->where('status', 'active')
                                    ->first();
            
            if ($coupon && $coupon->isValid()) {
                if ($coupon->type === 'fixed') {
                    $discount = min($coupon->value, $subtotal);
                } else {
                    $discount = ($subtotal * $coupon->value) / 100;
                    if ($coupon->maximum_discount) {
                        $discount = min($discount, $coupon->maximum_discount);
                    }
                }
                $couponCode = $coupon->code;
            }
        }

        $shippingCost = 0; // You can add shipping calculation logic here
        
        // Calculate GST Breakdown
        $shippingAddress = $request->shipping_address;
        $shippingState = $shippingAddress['state'] ?? '';
        $shopState = env('SHOP_STATE', 'Maharashtra');
        $isSameState = strcasecmp(trim($shippingState), trim($shopState)) === 0;

        $totalCgstAmount = 0.00;
        $totalSgstAmount = 0.00;
        $totalIgstAmount = 0.00;
        $totalTaxAmount = 0.00;

        $itemData = [];
        foreach ($cartItems as $item) {
            $itemSubtotal = $item->quantity * $item->price;
            
            // Proportional discount allocation
            $itemDiscount = 0;
            if ($subtotal > 0) {
                $itemDiscount = ($itemSubtotal / $subtotal) * $discount;
            }
            
            $netItemAmount = $itemSubtotal - $itemDiscount;
            
            // Product GST rates
            $cgstPct = (float) ($item->product->cgst ?? 0);
            $sgstPct = (float) ($item->product->sgst ?? 0);
            $igstPct = (float) ($item->product->igst ?? 0);
            
            // Fallback: If IGST rate is empty but CGST/SGST exist, combine them for IGST
            if ($igstPct == 0 && $cgstPct > 0) {
                $igstPct = $cgstPct + $sgstPct;
            }
            
            // Determine applicable rates based on state comparison
            if ($isSameState) {
                $cgstPercent = $cgstPct;
                $sgstPercent = $sgstPct;
                $igstPercent = 0.00;
            } else {
                $cgstPercent = 0.00;
                $sgstPercent = 0.00;
                $igstPercent = $igstPct;
            }
            
            $combinedTaxPercent = $cgstPercent + $sgstPercent + $igstPercent;
            
            if ($combinedTaxPercent > 0) {
                // Tax inclusive calculation: Base = Net / (1 + Rate/100)
                $taxableValue = $netItemAmount / (1 + ($combinedTaxPercent / 100));
                $itemTaxAmount = $netItemAmount - $taxableValue;
                
                if ($isSameState) {
                    $cgstAmount = $taxableValue * ($cgstPercent / 100);
                    $sgstAmount = $taxableValue * ($sgstPercent / 100);
                    $igstAmount = 0.00;
                } else {
                    $cgstAmount = 0.00;
                    $sgstAmount = 0.00;
                    $igstAmount = $taxableValue * ($igstPercent / 100);
                }
            } else {
                $itemTaxAmount = 0.00;
                $cgstAmount = 0.00;
                $sgstAmount = 0.00;
                $igstAmount = 0.00;
            }
            
            $cgstAmount = round($cgstAmount, 2);
            $sgstAmount = round($sgstAmount, 2);
            $igstAmount = round($igstAmount, 2);
            $itemTaxAmount = round($itemTaxAmount, 2);
            
            $totalCgstAmount += $cgstAmount;
            $totalSgstAmount += $sgstAmount;
            $totalIgstAmount += $igstAmount;
            $totalTaxAmount += $itemTaxAmount;
            
            $itemData[] = [
                'cart_item' => $item,
                'cgst_percent' => $cgstPercent,
                'sgst_percent' => $sgstPercent,
                'igst_percent' => $igstPercent,
                'cgst_amount' => $cgstAmount,
                'sgst_amount' => $sgstAmount,
                'igst_amount' => $igstAmount,
                'tax_amount' => $itemTaxAmount,
            ];
        }

        $totalCgstAmount = round($totalCgstAmount, 2);
        $totalSgstAmount = round($totalSgstAmount, 2);
        $totalIgstAmount = round($totalIgstAmount, 2);
        $totalTaxAmount = round($totalTaxAmount, 2);

        // Price is tax-inclusive, so total to pay is subtotal - discount + shipping cost
        $total = $subtotal - $discount + $shippingCost;

        DB::beginTransaction();
        try {
            // Create order in database
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'status' => 'pending_payment',
                'payment_status' => 'pending',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping_cost' => $shippingCost,
                'tax' => $totalTaxAmount,
                'cgst_amount' => $totalCgstAmount,
                'sgst_amount' => $totalSgstAmount,
                'igst_amount' => $totalIgstAmount,
                'total_amount' => $total,
                'payment_method' => 'razorpay',
                'shipping_address' => json_encode($request->shipping_address),
                'billing_address' => json_encode($request->billing_address ?? $request->shipping_address),
                'coupon_code' => $couponCode,
                'ordered_at' => now(),
            ]);

            // Store order items with GST breakdown
            foreach ($itemData as $data) {
                $item = $data['cart_item'];
                $order->items()->create([
                    'product_id' => $item->product->id,
                    'product_name' => $item->product->name,
                    'product_sku' => $item->product->sku,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->quantity * $item->price,
                    'cgst_percent' => $data['cgst_percent'],
                    'sgst_percent' => $data['sgst_percent'],
                    'igst_percent' => $data['igst_percent'],
                    'cgst_amount' => $data['cgst_amount'],
                    'sgst_amount' => $data['sgst_amount'],
                    'igst_amount' => $data['igst_amount'],
                    'tax_amount' => $data['tax_amount'],
                    'product_options' => $item->product_options,
                ]);
            }

            // Create Razorpay order
            $razorpayOrder = $this->razorpayApi->order->create([
                'receipt' => $order->order_number,
                'amount' => (int) round($total * 100),
                'currency' => config('razorpay.currency'),
                'payment_capture' => config('razorpay.payment_capture') ? 1 : 0,
            ]);

            // Update order with Razorpay order ID
            $order->update([
                'razorpay_order_id' => $razorpayOrder['id']
            ]);

            // Create payment record
            Payment::create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'razorpay_order_id' => $razorpayOrder['id'],
                'amount' => $total,
                'currency' => config('razorpay.currency'),
                'status' => 'created',
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Order created successfully',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'razorpay_order_id' => $razorpayOrder['id'],
                    'razorpay_key_id' => config('razorpay.key_id'),
                    'amount' => $total,
                    'currency' => config('razorpay.currency'),
                    'user' => [
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $request->shipping_address['phone'] ?? null,
                    ],
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Razorpay order creation failed: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify payment after successful payment
     */
    public function verifyPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify signature
        $attributes = [
            'razorpay_order_id' => $request->razorpay_order_id,
            'razorpay_payment_id' => $request->razorpay_payment_id,
            'razorpay_signature' => $request->razorpay_signature,
        ];

        //$this->razorpayApi->utility->verifyPaymentSignature($attributes);

        // Get order
        $order = Order::where('razorpay_order_id', $request->razorpay_order_id)->first();
        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found'
            ], 404);
        }

        // Check if already processed
        if ($order->payment_status === 'completed') {
            return response()->json([
                'status' => 'success',
                'message' => 'Payment already verified',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ]
            ]);
        }

        DB::beginTransaction();

        try {

            /**
             * ===========================================
             * 🟡 TEST MODE
             * ===========================================
             */
            if (env('RAZORPAY_TEST_MODE') == true) {

                $mockPayment = [
                    'method' => 'test_mode',
                    'email' => $order->email ?? null,
                    'contact' => $order->phone ?? null,
                    'status' => 'captured'
                ];

                $order->update([
                    'razorpay_payment_id' => $request->razorpay_payment_id,
                    'razorpay_signature' => $request->razorpay_signature,
                    'payment_status' => 'completed',
                    'status' => 'processing',
                    'paid_at' => now(),
                ]);

                Payment::where('razorpay_order_id', $request->razorpay_order_id)
                    ->update([
                        'razorpay_payment_id' => $request->razorpay_payment_id,
                        'razorpay_signature' => $request->razorpay_signature,
                        'status' => 'captured',
                        'method' => 'test_mode',
                        'razorpay_response' => $mockPayment,
                    ]);

                // go to shipping block
                goto shipping_block;
            }

            /**
             * ===========================================
             * 🔵 LIVE MODE
             * ===========================================
             */

            $this->razorpayApi->utility->verifyPaymentSignature([
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
            ]);

            $razorpayPayment = $this->razorpayApi->payment->fetch($request->razorpay_payment_id);

            $order->update([
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
                'payment_status' => 'completed',
                'status' => 'processing',
                'paid_at' => now(),
            ]);

            Payment::where('razorpay_order_id', $request->razorpay_order_id)
                ->update([
                    'razorpay_payment_id' => $request->razorpay_payment_id,
                    'razorpay_signature' => $request->razorpay_signature,
                    'status' => 'captured',
                    'method' => $razorpayPayment['method'] ?? null,
                    'email' => $razorpayPayment['email'] ?? null,
                    'contact' => $razorpayPayment['contact'] ?? null,
                    'razorpay_response' => $razorpayPayment->toArray(),
                ]);

            /**
             * ===========================================
             * 🔵 SHIPPING INTEGRATION (SHIPROCKET OR PARCELX)
             * ===========================================
             */
            shipping_block:

            try {
                $provider = setting('shipping_provider', 'shiprocket');

                if ($provider === 'parcelx') {
                    $parcelx = new \App\Services\ParcelXService();
                    $shipResponse = $parcelx->createOrder($order);

                    if ($shipResponse && isset($shipResponse['status']) && $shipResponse['status']) {
                        $pxData = $shipResponse['data'] ?? [];
                        $order->update([
                            'shiprocket_order_id'      => $pxData['order_number'] ?? null,
                            'shiprocket_shipment_id'   => $pxData['pickup_id'] ?? null,
                            'awb_code'                 => $pxData['awb_number'] ?? null,
                            'courier_company_id'       => $pxData['courier_code'] ?? null,
                            'shipment_data'            => json_encode(array_merge($shipResponse, ['provider' => 'parcelx'])),
                        ]);
                    } else {
                        Log::error("ParcelX DID NOT return successful status: " . json_encode($shipResponse));
                    }
                } else {
                    $shiprocket = new \App\Services\ShiprocketService();
                    $shipResponse = $shiprocket->createOrder($order);

                    if ($shipResponse && isset($shipResponse['order_id'])) {
                        $order->update([
                            'shiprocket_order_id'      => $shipResponse['order_id'] ?? null,
                            'shiprocket_shipment_id'   => $shipResponse['shipment_id'] ?? null,
                            'awb_code'                 => $shipResponse['awb_code'] ?? null,
                            'courier_company_id'       => $shipResponse['courier_company_id'] ?? null,
                            'shipment_data'            => json_encode(array_merge($shipResponse, ['provider' => 'shiprocket'])),
                        ]);
                    } else {
                        Log::error("Shiprocket DID NOT return order_id: " . json_encode($shipResponse));
                    }
                }

            } catch (\Exception $e) {
                Log::error("Shipping Integration Error: " . $e->getMessage());
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Payment verified & order synced to Shiprocket',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'shiprocket_order_id' => $order->shiprocket_order_id,
                    'awb_code' => $order->awb_code,
                ]
            ]);

        } catch (\Exception $e) {

            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Verification failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle Razorpay webhook
     */
    public function handleWebhook(Request $request)
    {
        $webhookSecret = config('razorpay.webhook_secret');
        $webhookSignature = $request->header('X-Razorpay-Signature');
        $webhookBody = $request->getContent();

        // Verify webhook signature
        try {
            $this->razorpayApi->utility->verifyWebhookSignature(
                $webhookBody,
                $webhookSignature,
                $webhookSecret
            );
        } catch (\Exception $e) {
            Log::error('Webhook signature verification failed: ' . $e->getMessage());
            return response()->json(['status' => 'error'], 400);
        }

        $event = $request->input('event');
        $payload = $request->input('payload');

        Log::info('Razorpay Webhook Received', ['event' => $event, 'payload' => $payload]);

        try {
            switch ($event) {
                case 'payment.authorized':
                case 'payment.captured':
                    $this->handlePaymentSuccess($payload['payment']['entity']);
                    break;

                case 'payment.failed':
                    $this->handlePaymentFailed($payload['payment']['entity']);
                    break;

                case 'order.paid':
                    $this->handleOrderPaid($payload['order']['entity']);
                    break;

                case 'refund.created':
                    $this->handleRefund($payload['refund']['entity']);
                    break;

                default:
                    Log::info('Unhandled webhook event: ' . $event);
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Webhook processing error: ' . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus($orderId, Request $request)
    {
        $user = $request->user();
        
        $order = Order::where('id', $orderId)
                     ->where('user_id', $user->id)
                     ->with(['payment', 'items.product'])
                     ->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment_status' => $order->payment_status,
                'order_status' => $order->status,
                'total_amount' => $order->total_amount,
                'razorpay_order_id' => $order->razorpay_order_id,
                'razorpay_payment_id' => $order->razorpay_payment_id,
                'paid_at' => $order->paid_at,
            ]
        ]);
    }

    /**
     * Initiate Razorpay Refund
     */
    public static function initiateRazorpayRefund($order, $amount, $reason = '')
    {
        if (env('RAZORPAY_TEST_MODE') == true) {
            Log::info("Razorpay Refund Mocked for Order {$order->order_number} of amount {$amount}");
            Payment::where('order_id', $order->id)->update(['status' => 'refunded']);
            return [
                'status' => 'success',
                'refund_id' => 'rfnd_mock_' . uniqid(),
                'amount' => $amount
            ];
        }

        try {
            $api = new Api(
                config('razorpay.key_id'),
                config('razorpay.key_secret')
            );

            $paymentId = $order->razorpay_payment_id;
            if (!$paymentId) {
                $paymentRecord = Payment::where('order_id', $order->id)->whereNotNull('razorpay_payment_id')->first();
                $paymentId = $paymentRecord ? $paymentRecord->razorpay_payment_id : null;
            }

            if (!$paymentId) {
                Log::error("Cannot refund Order {$order->order_number} - No Razorpay payment ID found");
                return null;
            }

            $refund = $api->refund->create([
                'payment_id' => $paymentId,
                'amount' => (int) round($amount * 100), // in paise
                'notes' => [
                    'order_number' => $order->order_number,
                    'reason' => $reason
                ]
            ]);

            Log::info("Razorpay Refund Successful for Order {$order->order_number}: " . $refund->id);
            Payment::where('order_id', $order->id)->update(['status' => 'refunded']);

            return [
                'status' => 'success',
                'refund_id' => $refund->id,
                'amount' => $amount
            ];

        } catch (\Exception $e) {
            Log::error("Razorpay Refund Exception for Order {$order->order_number}: " . $e->getMessage());
            return null;
        }
    }

    // Private helper methods for webhook handling

    private function handlePaymentSuccess($paymentData)
    {
        $payment = Payment::where('razorpay_payment_id', $paymentData['id'])->first();
        
        if (!$payment) {
            $payment = Payment::where('razorpay_order_id', $paymentData['order_id'])->first();
        }

        if ($payment) {
            $payment->update([
                'razorpay_payment_id' => $paymentData['id'],
                'status' => 'captured',
                'method' => $paymentData['method'] ?? null,
                'email' => $paymentData['email'] ?? null,
                'contact' => $paymentData['contact'] ?? null,
                'razorpay_response' => $paymentData,
            ]);

            $order = $payment->order;
            if ($order && $order->payment_status !== 'completed') {
                $order->markAsPaid();
                
                // Reduce inventory if not already done
                foreach ($order->items as $item) {
                    if ($item->product && $item->product->track_inventory) {
                        $item->product->decrement('quantity', $item->quantity);
                    }
                }
                
                // Clear cart
                Cart::where('user_id', $order->user_id)->delete();
            }
        }
    }

    private function handlePaymentFailed($paymentData)
    {
        $payment = Payment::where('razorpay_order_id', $paymentData['order_id'])->first();

        if ($payment) {
            $payment->update([
                'razorpay_payment_id' => $paymentData['id'] ?? null,
                'status' => 'failed',
                'error_description' => $paymentData['error_description'] ?? 'Payment failed',
                'razorpay_response' => $paymentData,
            ]);

            $order = $payment->order;
            if ($order) {
                $order->markAsFailed();
            }
        }
    }

    private function handleOrderPaid($orderData)
    {
        $order = Order::where('razorpay_order_id', $orderData['id'])->first();

        if ($order && $order->payment_status !== 'completed') {
            $order->markAsPaid();
        }
    }

    private function handleRefund($refundData)
    {
        $payment = Payment::where('razorpay_payment_id', $refundData['payment_id'])->first();

        if ($payment) {
            $payment->update([
                'status' => 'refunded',
                'razorpay_response' => array_merge($payment->razorpay_response ?? [], ['refund' => $refundData]),
            ]);

            $order = $payment->order;
            if ($order) {
                $order->update([
                    'payment_status' => 'refunded',
                    'status' => 'refunded',
                ]);
            }
        }
    }
}