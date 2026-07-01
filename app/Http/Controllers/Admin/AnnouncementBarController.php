<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnnouncementBar;
use Illuminate\Http\Request;

class AnnouncementBarController extends Controller
{
    public function index()
    {
        $announcement = AnnouncementBar::first();

        if (!$announcement) {
            $announcement = AnnouncementBar::create([
                'status' => 'active'
            ]);
        }

        return view('admin.announcement_bar.index', compact('announcement'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'text_before' => 'nullable|string|max:255',
            'highlight_text' => 'nullable|string|max:255',
            'text_after' => 'nullable|string|max:255',
            'button_text' => 'nullable|string|max:255',
            'button_url' => 'nullable|max:255',
            'status' => 'required'
        ]);

        $announcement = AnnouncementBar::first();

        if (!$announcement) {
            $announcement = new AnnouncementBar();
        }

        $announcement->fill($request->all());
        $announcement->save();

        return redirect()
            ->back()
            ->with('success', 'Announcement updated successfully.');
    }
}