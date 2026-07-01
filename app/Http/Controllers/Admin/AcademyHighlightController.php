<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademyHighlight;
use App\Models\TrainingCourse;
use Illuminate\Http\Request;

class AcademyHighlightController extends Controller
{
    public function index()
    {
        $highlights = AcademyHighlight::with('course')
                        ->latest()
                        ->get();

        return view(
            'admin.training.highlights.index',
            compact('highlights')
        );
    }

    public function create()
    {
        $courses = TrainingCourse::pluck(
            'name',
            'id'
        );

        return view(
            'admin.training.highlights.create',
            compact('courses')
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required',
            'title'     => 'required'
        ]);

        AcademyHighlight::create([
            'course_id'   => $request->course_id,
            'title'       => $request->title,
            'description' => $request->description
        ]);

        return redirect()
            ->route('admin.academy-highlights.index')
            ->with(
                'success',
                'Academy Highlight Added Successfully'
            );
    }

    public function edit($id)
    {
        $highlight = AcademyHighlight::findOrFail($id);

        $courses = TrainingCourse::pluck(
            'name',
            'id'
        );

        return view(
            'admin.training.highlights.edit',
            compact(
                'highlight',
                'courses'
            )
        );
    }

    public function update(Request $request,$id)
    {
        $request->validate([
            'course_id' => 'required',
            'title'     => 'required'
        ]);

        $highlight = AcademyHighlight::findOrFail($id);

        $highlight->update([
            'course_id'   => $request->course_id,
            'title'       => $request->title,
            'description' => $request->description
        ]);

        return redirect()
            ->route('admin.academy-highlights.index')
            ->with(
                'success',
                'Academy Highlight Updated Successfully'
            );
    }

    public function destroy($id)
    {
        $highlight = AcademyHighlight::findOrFail($id);

        $highlight->delete();

        return redirect()
            ->route('admin.academy-highlights.index')
            ->with(
                'success',
                'Academy Highlight Deleted Successfully'
            );
    }
}