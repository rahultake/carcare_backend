<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SpecializationItem;
use App\Models\SpecializationProgram;
use Illuminate\Http\Request;

class SpecializationItemController extends Controller
{
    public function index()
    {
        $items = SpecializationItem::with([
                    'specialization.course'
                ])
                ->latest()
                ->paginate(20);

        return view(
            'admin.training.specialization-items.index',
            compact('items')
        );
    }

    public function create()
    {
        $specializations = SpecializationProgram::with('course')
                            ->orderBy('title')
                            ->get();

        return view(
            'admin.training.specialization-items.create',
            compact('specializations')
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'specialization_id' => 'required',
            'item' => 'required|max:255'
        ]);

        SpecializationItem::create([
            'specialization_id' => $request->specialization_id,
            'item' => $request->item
        ]);

        return redirect()
            ->route('admin.specialization-items.index')
            ->with(
                'success',
                'Specialization Item Added Successfully'
            );
    }

    public function show($id)
    {
        $item = SpecializationItem::with([
                    'specialization.course'
                ])->findOrFail($id);

        return view(
            'admin.training.specialization-items.show',
            compact('item')
        );
    }

    public function edit($id)
    {
        $item = SpecializationItem::findOrFail($id);

        $specializations = SpecializationProgram::with('course')
                            ->orderBy('title')
                            ->get();

        return view(
            'admin.training.specialization-items.edit',
            compact(
                'item',
                'specializations'
            )
        );
    }

    public function update(Request $request,$id)
    {
        $request->validate([
            'specialization_id' => 'required',
            'item' => 'required|max:255'
        ]);

        $item = SpecializationItem::findOrFail($id);

        $item->update([
            'specialization_id' => $request->specialization_id,
            'item' => $request->item
        ]);

        return redirect()
            ->route('admin.specialization-items.index')
            ->with(
                'success',
                'Specialization Item Updated Successfully'
            );
    }

    public function destroy($id)
    {
        $item = SpecializationItem::findOrFail($id);

        $item->delete();

        return redirect()
            ->route('admin.specialization-items.index')
            ->with(
                'success',
                'Specialization Item Deleted Successfully'
            );
    }
}