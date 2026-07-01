<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductReview;
use App\Models\OrderItem;
use App\Models\Product;

class ProductReviewController extends Controller
{
    public function index(Product $product)
    {
        $reviews = ProductReview::with('user:id,name') // fetch reviewer name
            ->where('product_id', $product->id)
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $reviews
        ]);
    }
    public function store(Request $request, Product $product)
    {
        $user = $request->user();

        // Validate request
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ]);

        // Check if the user ordered the product
        $ordered = OrderItem::whereHas('order', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->where('product_id', $product->id)->exists();

        if (!$ordered) {
            return response()->json([
                'status' => 'error',
                'message' => 'You can only review products you have purchased.'
            ], 403);
        }

        // Prevent duplicate review
        $existing = ProductReview::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            return response()->json([
                'status' => 'error',
                'message' => 'You have already reviewed this product.'
            ], 409);
        }

        // Save review
        $review = ProductReview::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => $request->rating,
            'review' => $request->review,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Review added successfully.',
            'review' => $review
        ]);
    }
}
?>