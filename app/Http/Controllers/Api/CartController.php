<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'product_options' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Product::findOrFail($request->product_id);
        
        // Check if product is active and in stock
        if ($product->status !== 'active') {
            return response()->json([
                'status' => 'error',
                'message' => 'Product is not available'
            ], 400);
        }

        if ($product->stock_status === 'out_of_stock') {
            return response()->json([
                'status' => 'error',
                'message' => 'Product is out of stock'
            ], 400);
        }

        if ($product->track_inventory && $product->quantity < $request->quantity) {
            return response()->json([
                'status' => 'error',
                'message' => 'Insufficient stock. Only ' . $product->quantity . ' items available'
            ], 400);
        }

        $user = $request->user();
        
        // Check if item already exists in cart
        $cartItem = Cart::where('user_id', $user->id)
                       ->where('product_id', $product->id)
                       ->first();

        if ($cartItem) {
            $newQuantity = $cartItem->quantity + $request->quantity;
            
            if ($product->track_inventory && $product->quantity < $newQuantity) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot add more items. Only ' . ($product->quantity - $cartItem->quantity) . ' more items available'
                ], 400);
            }
            
            $cartItem->update([
                'quantity' => $newQuantity,
                'price' => $product->price, // Update price in case it changed
            ]);
        } else {
            $cartItem = Cart::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'quantity' => $request->quantity,
                'price' => $product->price,
                'product_options' => $request->product_options,
            ]);
        }

        $cartItem->load(['product.images', 'product.categories']);

        return response()->json([
            'status' => 'success',
            'message' => 'Product added to cart successfully',
            'data' => [
                'cart_item' => $this->transformCartItem($cartItem)
            ]
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        
        $cartItems = Cart::where('user_id', $user->id)
                        ->with(['product.images', 'product.categories'])
                        ->get();

        $transformedItems = $cartItems->map(function ($item) {
            return $this->transformCartItem($item);
        });

        $subtotal = $cartItems->sum(function ($item) {
            return $item->quantity * $item->price;
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'cart_items' => $transformedItems,
                'summary' => [
                    'items_count' => $cartItems->count(),
                    'total_quantity' => $cartItems->sum('quantity'),
                    'subtotal' => (float) $subtotal,
                ]
            ]
        ]);
    }

    public function remove(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cart_item_id' => 'required|exists:carts,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $cartItem = Cart::where('id', $request->cart_item_id)
                        ->where('user_id', $user->id)
                        ->first();

        if (!$cartItem) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cart item not found'
            ], 404);
        }

        $cartItem->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Cart item removed successfully'
        ]);
    }

    public function checkout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shipping_address'                    => 'required|array',
            'shipping_address.name'               => 'required|string|max:255',
            'shipping_address.phone'              => 'required|string|max:20',
            'shipping_address.address_line_1'     => 'required|string|max:255',
            'shipping_address.address_line_2'     => 'nullable|string|max:255',
            'shipping_address.city'               => 'required|string|max:100',
            'shipping_address.state'              => 'required|string|max:100',
            'shipping_address.postal_code'        => 'required|string|max:20',
            'shipping_address.country'            => 'required|string|max:100',
            'shipping_address.company_name'       => 'nullable|string|max:255',
            'shipping_address.gstin_number'       => 'nullable|string|max:20',
            'billing_address'                     => 'nullable|array',
            'billing_address.name'                => 'nullable|string|max:255',
            'billing_address.phone'               => 'nullable|string|max:20',
            'billing_address.address_line_1'      => 'nullable|string|max:255',
            'billing_address.address_line_2'      => 'nullable|string|max:255',
            'billing_address.city'                => 'nullable|string|max:100',
            'billing_address.state'               => 'nullable|string|max:100',
            'billing_address.postal_code'         => 'nullable|string|max:20',
            'billing_address.country'             => 'nullable|string|max:100',
            'billing_address.company_name'        => 'nullable|string|max:255',
            'billing_address.gstin_number'        => 'nullable|string|max:20',
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
        $coupon = null;

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
            }
        }

        // Calculate GST Breakdown
        $shippingAddress = $request->shipping_address;
        $shippingState = $shippingAddress['state'] ?? '';
        // Note: $isSameState is now determined per-item using each product's merchant_state

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
            
            // Per-product merchant state; fallback to global SHOP_STATE
            $productMerchantState = !empty($item->product->merchant_state)
                ? $item->product->merchant_state
                : env('SHOP_STATE', 'Maharashtra');
            $isSameState = strcasecmp(trim($shippingState), trim($productMerchantState)) === 0;
            
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

        $total = $subtotal - $discount;

        // Store order
        DB::beginTransaction();
        try {
            $order = \App\Models\Order::create([
                'user_id' => $user->id,
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'status' => 'pending',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $totalTaxAmount,
                'cgst_amount' => $totalCgstAmount,
                'sgst_amount' => $totalSgstAmount,
                'igst_amount' => $totalIgstAmount,
                'total_amount' => $total,
                'payment_method' => $request->payment_method ?? 'cod',
                'shipping_address' => json_encode($request->shipping_address),
                'billing_address' => json_encode($request->billing_address ?? $request->shipping_address),
                'ordered_at' => now(),
            ]);

            // Store order items with GST breakdown
            foreach ($itemData as $data) {
                $item = $data['cart_item'];
                \App\Models\OrderItem::create([
                    'order_id' => $order->id,
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
                ]);

                // Reduce stock if tracking inventory
                if ($item->product->track_inventory) {
                    $item->product->decrement('quantity', $item->quantity);
                }
            }

            // Clear cart after checkout
            Cart::where('user_id', $user->id)->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Order placed successfully',
                'order_id' => $order->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Checkout failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function transformCartItem($cartItem)
    {
        return [
            'id' => $cartItem->id,
            'quantity' => $cartItem->quantity,
            'price' => (float) $cartItem->price,
            'total' => (float) $cartItem->total,
            'product_options' => $cartItem->product_options,
            'product' => [
                'id' => $cartItem->product->id,
                'name' => $cartItem->product->name,
                'slug' => $cartItem->product->slug,
                'sku' => $cartItem->product->sku,
                'brand' => $cartItem->product->brand,
                'hsn_code' => $cartItem->product->categories->first(fn($cat) => !empty($cat->hsn_code))?->hsn_code,
                'price' => (float) $cartItem->product->price,
                'cgst' => (float) ($cartItem->product->cgst ?? 0),
                'sgst' => (float) ($cartItem->product->sgst ?? 0),
                'igst' => (float) ($cartItem->product->igst ?? 0),
                'stock_status' => $cartItem->product->stock_status,
                'quantity_available' => $cartItem->product->quantity,
                'primary_image' => $cartItem->product->images->where('is_primary', true)->first() 
                    ? asset('storage/' . $cartItem->product->images->where('is_primary', true)->first()->image_path)
                    : ($cartItem->product->images->first() ? asset('storage/' . $cartItem->product->images->first()->image_path) : null),
            ],
        ];
    }
}