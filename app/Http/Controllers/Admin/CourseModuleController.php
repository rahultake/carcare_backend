<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseModule;
use App\Models\TrainingCourse;
use Illuminate\Http\Request;

class CourseModuleController extends Controller
{
    public function index()
    {
        $modules = CourseModule::with('course')
                    ->latest()
                    ->get();

        return view(
            'admin.training.modules.index',
            compact('modules')
        );
    }

    public function create()
    {
        $courses = TrainingCourse::pluck(
            'name',
            'id'
        );

        return view(
            'admin.training.modules.create',
            compact('courses')
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required',
            'day_name'  => 'required',
            'title'     => 'required'
        ]);

        CourseModule::create([
            'course_id' => $request->course_id,
            'day_name'  => $request->day_name,
            'title'     => $request->title
        ]);

        return redirect()
            ->route('admin.course-modules.index')
            ->with(
                'success',
                'Course Module Created Successfully'
            );
    }

    public function edit($id)
    {
        $module = CourseModule::findOrFail($id);

        $courses = TrainingCourse::pluck(
            'name',
            'id'
        );

        return view(
            'admin.training.modules.edit',
            compact(
                'module',
                'courses'
            )
        );
    }

    public function update(
        Request $request,
        $id
    )
    {
        $request->validate([
            'course_id' => 'required',
            'day_name'  => 'required',
            'title'     => 'required'
        ]);

        $module = CourseModule::findOrFail($id);

        $module->update([
            'course_id' => $request->course_id,
            'day_name'  => $request->day_name,
            'title'     => $request->title
        ]);

        return redirect()
            ->route('admin.course-modules.index')
            ->with(
                'success',
                'Course Module Updated Successfully'
            );
    }

    public function destroy($id)
    {
        $module = CourseModule::findOrFail($id);

        $module->delete();

        return redirect()
            ->route('admin.course-modules.index')
            ->with(
                'success',
                'Course Module Deleted Successfully'
            );
    }
}