<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\UserAddressController;
use App\Http\Controllers\Api\ProductReviewController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\AnnouncementBarController;
use App\Http\Controllers\Api\TrainingInquiryController;
use App\Http\Controllers\Api\TrainingCourseController;


// Authentication APIs
Route::middleware('api')->group(function () {

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
        Route::get('user', [AuthController::class, 'user']);
    });
});

// Banner/Slider APIs
Route::prefix('banners')->group(function () {
    Route::get('/', [BannerController::class, 'index']);
});

// Categories, Subcategories, Brands, Navigation APIs
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/subcategories', [CategoryController::class, 'subcategories']);
    Route::get('/brands', [CategoryController::class, 'brands']);
    Route::get('/nav-links', [CategoryController::class, 'navLinks']);
});

Route::get('products/{product}/reviews', [ProductReviewController::class, 'index']);
Route::middleware('auth:sanctum')->post('/products/{product}/review', [ProductReviewController::class, 'store']);

Route::get('announcement-bar', [AnnouncementBarController::class, 'index']);

// Product APIs
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/filter', [ProductController::class, 'filter']);
    Route::get('/{slug}/related', [ProductController::class, 'related']);
    Route::get('/{slug}', [ProductController::class, 'show']);
});

// Cart and Checkout APIs (Protected)
Route::middleware('auth:sanctum')->prefix('cart')->group(function () {
    Route::post('/', [CartController::class, 'add']);
    Route::get('/', [CartController::class, 'index']);
    Route::post('/remove', [CartController::class, 'remove']);
    Route::post('/checkout', [CartController::class, 'checkout']);
});


// Payment APIs (Protected)
Route::middleware('auth:sanctum')->prefix('payment')->group(function () {
    Route::post('/create-order', [PaymentController::class, 'createOrder']);
    Route::post('/verify', [PaymentController::class, 'verifyPayment']);
    Route::get('/status/{orderId}', [PaymentController::class, 'getPaymentStatus']);
});

// Razorpay Webhook (No auth - verified by signature)
Route::post('payment/webhook', [PaymentController::class, 'handleWebhook']);

// Shiprocket Webhook (No auth - token verification handled in controller)
Route::post('shiprocket/webhook', [App\Http\Controllers\Api\ShiprocketWebhookController::class, 'handleWebhook']);

// ParcelX Webhook (No auth - status updates)
Route::post('parcelx/webhook', [App\Http\Controllers\Api\ParcelXWebhookController::class, 'handle']);

// Order APIs (Protected)
Route::middleware('auth:sanctum')->prefix('orders')->group(function () {

    Route::get('/history', [OrderController::class, 'orderHistory']); // <-- must be before {id}

    Route::get('/', [OrderController::class, 'index']);
    Route::get('/{id}', [OrderController::class, 'show']);
    Route::post('/{id}/cancel', [OrderController::class, 'cancel']);
    Route::post('/{id}/return', [OrderController::class, 'requestReturn']);
});

// Wishlist APIs (Protected)
Route::middleware('auth:sanctum')->prefix('wishlist')->group(function () {
    Route::post('/', [WishlistController::class, 'add']);
    Route::get('/', [WishlistController::class, 'index']);
    Route::post('/remove', [WishlistController::class, 'remove']);
});

// Blog APIs
Route::prefix('blogs')->group(function () {
    Route::get('/categories', [BlogController::class, 'categories']);         // Move this first
    Route::get('/recent-blogs/{limit?}', [BlogController::class, 'recentBlogs']);
    Route::get('/', [BlogController::class, 'allBlogs']);
    Route::get('/{slug}', [BlogController::class, 'singleBlog']);
});

Route::middleware('auth:sanctum')->prefix('user/addresses')->group(function () {
    Route::get('/', [UserAddressController::class, 'index']);
    Route::post('/', [UserAddressController::class, 'store']);
    Route::put('/{id}', [UserAddressController::class, 'update']);
    Route::delete('/{id}', [UserAddressController::class, 'destroy']);
});

Route::post('coupons/validate', [CouponController::class, 'validateCoupon']);

// Contact API
Route::post('contact', [ContactController::class, 'store']);

Route::post('training-inquiry', [TrainingInquiryController::class, 'store']);

Route::get('training-courses', [TrainingCourseController::class, 'index']);
Route::get('training-courses/{slug}', [TrainingCourseController::class, 'show']);

});