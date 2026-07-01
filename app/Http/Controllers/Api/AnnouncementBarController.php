<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnnouncementBar;
use Illuminate\Http\Request;

class AnnouncementBarController extends Controller
{
    public function index()
    {
        $announcement = AnnouncementBar::where('status', 'active')->first();

        if (!$announcement) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active announcement found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $announcement->id,
                'text_before' => $announcement->text_before,
                'highlight_text' => $announcement->highlight_text,
                'text_after' => $announcement->text_after,
                'button_text' => $announcement->button_text,
                'button_url' => $announcement->button_url,
                'status' => $announcement->status,
            ]
        ]);
    }
}