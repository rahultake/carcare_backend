<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SpecializationProgram;
use App\Models\TrainingCourse;
use Illuminate\Http\Request;

class SpecializationProgramController extends Controller
{
    public function index()
    {
        $programs = SpecializationProgram::with('course')
                    ->latest()
                    ->paginate(20);

        return view(
            'admin.training.specialization-programs.index',
            compact('programs')
        );
    }

    public function create()
    {
        $courses = TrainingCourse::pluck(
            'name',
            'id'
        );

        return view(
            'admin.training.specialization-programs.create',
            compact('courses')
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required',
            'title' => 'required|max:255',
            'description' => 'required',
            'image' => 'nullable|image'
        ]);

        $image = '';

        if($request->hasFile('image'))
        {
            $file = $request->file('image');

            $image = time().'_'.$file->getClientOriginalName();

            $file->move(
                public_path('uploads/specialization-programs'),
                $image
            );
        }

        SpecializationProgram::create([
            'course_id' => $request->course_id,
            'title' => $request->title,
            'description' => $request->description,
            'image' => $image
        ]);

        return redirect()
            ->route('admin.specialization-programs.index')
            ->with(
                'success',
                'Program Added Successfully'
            );
    }

    public function show($id)
    {
        $program = SpecializationProgram::with([
            'course',
            'items'
        ])->findOrFail($id);

        return view(
            'admin.training.specialization-programs.show',
            compact('program')
        );
    }

    public function edit($id)
    {
        $program = SpecializationProgram::findOrFail($id);

        $courses = TrainingCourse::pluck(
            'name',
            'id'
        );

        return view(
            'admin.training.specialization-programs.edit',
            compact(
                'program',
                'courses'
            )
        );
    }

    public function update(Request $request,$id)
    {
        $program = SpecializationProgram::findOrFail($id);

        $image = $program->image;

        if($request->hasFile('image'))
        {
            $file = $request->file('image');

            $image = time().'_'.$file->getClientOriginalName();

            $file->move(
                public_path('uploads/specialization-programs'),
                $image
            );
        }

        $program->update([
            'course_id' => $request->course_id,
            'title' => $request->title,
            'description' => $request->description,
            'image' => $image
        ]);

        return redirect()
            ->route('admin.specialization-programs.index')
            ->with(
                'success',
                'Program Updated Successfully'
            );
    }

    public function destroy($id)
    {
        $program = SpecializationProgram::findOrFail($id);

        $program->delete();

        return redirect()
            ->route('admin.specialization-programs.index')
            ->with(
                'success',
                'Program Deleted Successfully'
            );
    }
}