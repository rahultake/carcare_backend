<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Blog;
use App\Models\Category;

class BlogController extends Controller
{
    // Get all blogs
    public function allBlogs()
    {
        $blogs = Blog::with('category') // assuming relation
                    ->orderBy('created_at', 'desc')
                    ->get();

        return response()->json([
            'status' => 'success',
            'data' => $blogs
        ]);
    }

    // Get single blog by slug
    public function singleBlog($slug)
    {
        $blog = Blog::with('category')
                    ->where('slug', $slug)
                    ->first();

        if (!$blog) {
            return response()->json([
                'status' => 'error',
                'message' => 'Blog not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $blog
        ]);
    }

    // Get recent blogs with limit
    public function recentBlogs($limit = 5)
    {
        $blogs = Blog::with('category')
                    ->orderBy('created_at', 'desc')
                    ->take($limit)
                    ->get();

        return response()->json([
            'status' => 'success',
            'data' => $blogs
        ]);
    }
    
    public function categories()
    {
        $categories = Category::select('id', 'name', 'slug')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'categories' => $categories
        ]);
    }
}
?>