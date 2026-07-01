<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BenefitItem;
use App\Models\TrainingBenefit;
use Illuminate\Http\Request;

class BenefitItemController extends Controller
{
    public function index()
    {
        $items = BenefitItem::with('benefit.course')
                    ->latest()
                    ->get();

        return view(
            'admin.training.benefit-items.index',
            compact('items')
        );
    }

    public function create()
    {
        $benefits = TrainingBenefit::with('course')
                        ->get();

        return view(
            'admin.training.benefit-items.create',
            compact('benefits')
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'benefit_id' => 'required',
            'item'       => 'required'
        ]);

        BenefitItem::create([
            'benefit_id' => $request->benefit_id,
            'item'       => $request->item
        ]);

        return redirect()
            ->route('admin.benefit-items.index')
            ->with(
                'success',
                'Benefit Item Added Successfully'
            );
    }

    public function edit($id)
    {
        $item = BenefitItem::findOrFail($id);

        $benefits = TrainingBenefit::with('course')
                        ->get();

        return view(
            'admin.training.benefit-items.edit',
            compact(
                'item',
                'benefits'
            )
        );
    }

    public function update(Request $request,$id)
    {
        $request->validate([
            'benefit_id' => 'required',
            'item'       => 'required'
        ]);

        $item = BenefitItem::findOrFail($id);

        $item->update([
            'benefit_id' => $request->benefit_id,
            'item'       => $request->item
        ]);

        return redirect()
            ->route('admin.benefit-items.index')
            ->with(
                'success',
                'Benefit Item Updated Successfully'
            );
    }

    public function destroy($id)
    {
        $item = BenefitItem::findOrFail($id);

        $item->delete();

        return redirect()
            ->route('admin.benefit-items.index')
            ->with(
                'success',
                'Benefit Item Deleted Successfully'
            );
    }
}