<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingCourse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TrainingCourseController extends Controller
{
    public function index()
    {
        $courses = TrainingCourse::latest()->paginate(20);

        return view('admin.training.courses.index',compact('courses'));
    }

    public function create()
    {
        return view('admin.training.courses.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'=>'required|max:255',
            'duration'=>'required',
            'certification'=>'required'
        ]);

        $data = $request->all();

        $data['slug'] = Str::slug($request->name);

        if($request->hasFile('icon_image'))
        {
            $file = $request->file('icon_image');

            $filename = time().'_'.$file->getClientOriginalName();

            $file->move(public_path('uploads/training'),$filename);

            $data['icon_image'] = $filename;
        }

        TrainingCourse::create($data);

        return redirect()
            ->route('admin.training-courses.index')
            ->with('success','Course Added Successfully');
    }

    public function show($id)
    {
        $course = TrainingCourse::findOrFail($id);

        return view('admin.training.courses.show',compact('course'));
    }

    public function edit($id)
    {
        $course = TrainingCourse::findOrFail($id);

        return view('admin.training.courses.edit',compact('course'));
    }

    public function update(Request $request,$id)
    {
        $course = TrainingCourse::findOrFail($id);

        $request->validate([
            'name'=>'required|max:255',
            'duration'=>'required',
            'certification'=>'required'
        ]);

        $data = $request->all();

        $data['slug'] = Str::slug($request->name);

        if($request->hasFile('icon_image'))
        {
            $file = $request->file('icon_image');

            $filename = time().'_'.$file->getClientOriginalName();

            $file->move(public_path('uploads/training'),$filename);

            $data['icon_image'] = $filename;
        }

        $course->update($data);

        return redirect()
            ->route('admin.training-courses.index')
            ->with('success','Course Updated Successfully');
    }

    public function destroy($id)
    {
        $course = TrainingCourse::findOrFail($id);

        $course->delete();

        return redirect()
            ->route('admin.training-courses.index')
            ->with('success','Course Deleted Successfully');
    }
}