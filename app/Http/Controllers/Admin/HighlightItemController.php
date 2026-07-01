<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HighlightItem;
use App\Models\AcademyHighlight;
use Illuminate\Http\Request;

class HighlightItemController extends Controller
{
    public function index()
    {
        $items = HighlightItem::with([
                    'highlight.course'
                ])
                ->latest()
                ->paginate(20);

        return view(
            'admin.training.highlight-items.index',
            compact('items')
        );
    }

    public function create()
    {
        $highlights = AcademyHighlight::with('course')
                        ->orderBy('title')
                        ->get();

        return view(
            'admin.training.highlight-items.create',
            compact('highlights')
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'highlight_id' => 'required',
            'item' => 'required|max:255'
        ]);

        HighlightItem::create([
            'highlight_id' => $request->highlight_id,
            'item' => $request->item
        ]);

        return redirect()
            ->route('admin.highlight-items.index')
            ->with(
                'success',
                'Highlight Item Added Successfully'
            );
    }

    public function show($id)
    {
        $item = HighlightItem::with([
                    'highlight.course'
                ])->findOrFail($id);

        return view(
            'admin.training.highlight-items.show',
            compact('item')
        );
    }

    public function edit($id)
    {
        $item = HighlightItem::findOrFail($id);

        $highlights = AcademyHighlight::with('course')
                        ->orderBy('title')
                        ->get();

        return view(
            'admin.training.highlight-items.edit',
            compact(
                'item',
                'highlights'
            )
        );
    }

    public function update(Request $request,$id)
    {
        $request->validate([
            'highlight_id' => 'required',
            'item' => 'required|max:255'
        ]);

        $item = HighlightItem::findOrFail($id);

        $item->update([
            'highlight_id' => $request->highlight_id,
            'item' => $request->item
        ]);

        return redirect()
            ->route('admin.highlight-items.index')
            ->with(
                'success',
                'Highlight Item Updated Successfully'
            );
    }

    public function destroy($id)
    {
        $item = HighlightItem::findOrFail($id);

        $item->delete();

        return redirect()
            ->route('admin.highlight-items.index')
            ->with(
                'success',
                'Highlight Item Deleted Successfully'
            );
    }
}