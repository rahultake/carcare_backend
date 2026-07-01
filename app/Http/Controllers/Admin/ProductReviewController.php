<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    public function index()
    {
        $reviews = ProductReview::with(['user', 'product'])
            ->latest()
            ->get();

        $users = User::orderBy('name')->get();

        $products = Product::orderBy('name')->get();

        return view('admin.reviews.index', compact(
            'reviews',
            'users',
            'products'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'product_id' => 'required',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'required|string',
        ]);

        ProductReview::create([
            'user_id' => $request->user_id,
            'product_id' => $request->product_id,
            'rating' => $request->rating,
            'review' => $request->review,
            'status' => 'unpublished',
        ]);

        return redirect()
            ->route('admin.reviews.index')
            ->with('success', 'Review added successfully');
    }

    public function toggleStatus($id)
    {
        $review = ProductReview::findOrFail($id);

        $review->status =
            $review->status == 'published'
            ? 'unpublished'
            : 'published';

        $review->save();

        return redirect()
            ->back()
            ->with('success', 'Review status updated');
    }

    public function destroy($id)
    {
        $review = ProductReview::findOrFail($id);

        $review->delete();

        return redirect()
            ->back()
            ->with('success', 'Review deleted successfully');
    }
}