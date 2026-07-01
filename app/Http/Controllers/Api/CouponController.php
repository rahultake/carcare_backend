<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;

class CouponController extends Controller
{
    public function validateCoupon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $code = $request->code;
        $productIds = $request->product_ids;

        // 🔍 Find coupon
        $coupon = Coupon::where('code', $code)
            ->where('status', 1)
            ->first();

        if (!$coupon) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid coupon code'
            ], 404);
        }

        // ⏱ Check date validity
        $now = now();

        if ($coupon->starts_at && $coupon->starts_at > $now) {
            return response()->json([
                'status' => 'error',
                'message' => 'Coupon not started yet'
            ]);
        }

        if ($coupon->expires_at && $coupon->expires_at < $now) {
            return response()->json([
                'status' => 'error',
                'message' => 'Coupon expired'
            ]);
        }

        // 📦 Get products
        $products = Product::whereIn('id', $productIds)->get();

        if ($products->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No valid products found'
            ]);
        }

        // 💰 Calculate total
        $totalAmount = $products->sum('price');

        // 💡 Check minimum amount
        if ($coupon->minimum_amount && $totalAmount < $coupon->minimum_amount) {
            return response()->json([
                'status' => 'error',
                'message' => 'Minimum amount not reached'
            ]);
        }

        $applicableProducts = [];

        if (!empty($coupon->applicable_products)) {

        $applicableProducts = is_array($coupon->applicable_products)
            ? $coupon->applicable_products
            : explode(',', $coupon->applicable_products);

        }

        $excludedProducts = [];

        if (!empty($coupon->excluded_products)) {

        $excludedProducts = is_array($coupon->excluded_products)
            ? $coupon->excluded_products
            : explode(',', $coupon->excluded_products);

        }

        // If applicable_products exists → must match
        if (!empty($applicableProducts)) {
            $matched = array_intersect($productIds, $applicableProducts);

            if (empty($matched)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Coupon not applicable to selected products'
                ]);
            }
        }

        // Check excluded products
        if (!empty($excludedProducts)) {
            $blocked = array_intersect($productIds, $excludedProducts);

            if (!empty($blocked)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Coupon not valid for some selected products'
                ]);
            }
        }

        // 🧮 Calculate discount
        $discount = 0;

        if ($coupon->type === 'percentage') {
            $discount = ($totalAmount * $coupon->value) / 100;

            if ($coupon->maximum_discount) {
                $discount = min($discount, $coupon->maximum_discount);
            }
        } else {
            $discount = $coupon->value;
        }

        // 🚀 Final response
        return response()->json([
            'status' => 'success',
            'message' => 'Coupon applied successfully',
            'data' => [
                'coupon' => [
                    'id' => $coupon->id,
                    'code' => $coupon->code,
                    'name' => $coupon->name,
                    'type' => $coupon->type,
                    'value' => $coupon->value,
                    'free_shipping' => $coupon->free_shipping,
                ],
                'cart_total' => $totalAmount,
                'discount' => round($discount, 2),
                'final_total' => round($totalAmount - $discount, 2),
            ]
        ]);
    }
}
?>