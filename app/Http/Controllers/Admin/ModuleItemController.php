<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ModuleItem;
use App\Models\CourseModule;
use Illuminate\Http\Request;

class ModuleItemController extends Controller
{
    public function index()
    {
        $items = ModuleItem::with('module.course')
                    ->latest()
                    ->get();

        return view(
            'admin.training.module-items.index',
            compact('items')
        );
    }

    public function create()
    {
        $modules = CourseModule::with('course')
                    ->get();

        return view(
            'admin.training.module-items.create',
            compact('modules')
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'module_id' => 'required',
            'item'      => 'required'
        ]);

        ModuleItem::create([
            'module_id' => $request->module_id,
            'item'      => $request->item
        ]);

        return redirect()
            ->route('admin.module-items.index')
            ->with(
                'success',
                'Module Item Added Successfully'
            );
    }

    public function edit($id)
    {
        $item = ModuleItem::findOrFail($id);

        $modules = CourseModule::with('course')
                    ->get();

        return view(
            'admin.training.module-items.edit',
            compact(
                'item',
                'modules'
            )
        );
    }

    public function update(
        Request $request,
        $id
    )
    {
        $request->validate([
            'module_id' => 'required',
            'item'      => 'required'
        ]);

        $item = ModuleItem::findOrFail($id);

        $item->update([
            'module_id' => $request->module_id,
            'item'      => $request->item
        ]);

        return redirect()
            ->route('admin.module-items.index')
            ->with(
                'success',
                'Module Item Updated Successfully'
            );
    }

    public function destroy($id)
    {
        $item = ModuleItem::findOrFail($id);

        $item->delete();

        return redirect()
            ->route('admin.module-items.index')
            ->with(
                'success',
                'Module Item Deleted Successfully'
            );
    }
}