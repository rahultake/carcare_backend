<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingBenefit;
use App\Models\TrainingCourse;
use Illuminate\Http\Request;

class TrainingBenefitController extends Controller
{
    public function index()
    {
        $benefits = TrainingBenefit::with('course')
                    ->latest()
                    ->get();

        return view(
            'admin.training.benefits.index',
            compact('benefits')
        );
    }

    public function create()
    {
        $courses = TrainingCourse::pluck(
            'name',
            'id'
        );

        return view(
            'admin.training.benefits.create',
            compact('courses')
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required',
            'title' => 'required'
        ]);

        $image = '';

        if($request->hasFile('image'))
        {
            $file = $request->file('image');

            $image = time().'_'.$file->getClientOriginalName();

            $file->move(
                public_path('uploads/training-benefits'),
                $image
            );
        }

        TrainingBenefit::create([
            'course_id' => $request->course_id,
            'title' => $request->title,
            'description' => $request->description,
            'image' => $image
        ]);

        return redirect()
            ->route('admin.training-benefits.index')
            ->with(
                'success',
                'Benefit Added Successfully'
            );
    }

    public function edit($id)
    {
        $benefit = TrainingBenefit::findOrFail($id);

        $courses = TrainingCourse::pluck(
            'name',
            'id'
        );

        return view(
            'admin.training.benefits.edit',
            compact(
                'benefit',
                'courses'
            )
        );
    }

    public function update(
        Request $request,
        $id
    )
    {
        $benefit = TrainingBenefit::findOrFail($id);

        $image = $benefit->image;

        if($request->hasFile('image'))
        {
            $file = $request->file('image');

            $image = time().'_'.$file->getClientOriginalName();

            $file->move(
                public_path('uploads/training-benefits'),
                $image
            );
        }

        $benefit->update([
            'course_id' => $request->course_id,
            'title' => $request->title,
            'description' => $request->description,
            'image' => $image
        ]);

        return redirect()
            ->route('admin.training-benefits.index')
            ->with(
                'success',
                'Benefit Updated Successfully'
            );
    }

    public function destroy($id)
    {
        $benefit = TrainingBenefit::findOrFail($id);

        $benefit->delete();

        return redirect()
            ->route('admin.training-benefits.index')
            ->with(
                'success',
                'Benefit Deleted Successfully'
            );
    }
}