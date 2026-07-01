<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrainingCourse;

class TrainingCourseController extends Controller
{
    public function index()
    {
        $courses = TrainingCourse::with([
            'modules.items',
            'benefits.items',
            'highlights.items',
            'specializationPrograms.items'
        ])
        ->where('status', 1)
        ->orderBy('id')
        ->get();

        $response = $courses->map(function ($course) {
            return $this->formatCourse($course);
        });

        return response()->json([
            'status' => 'success',
            'data' => $response
        ]);
    }

    public function show($slug)
    {
        $course = TrainingCourse::with([
            'modules.items',
            'benefits.items',
            'highlights.items',
            'specializationPrograms.items'
        ])
        ->where('slug', $slug)
        ->where('status', 1)
        ->first();

        if (!$course) {
            return response()->json([
                'status' => 'error',
                'message' => 'Course not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->formatCourse($course)
        ]);
    }

    private function formatCourse($course)
    {
        return [
            'id' => (string)$course->id,
            'slug' => $course->slug,
            'name' => $course->name,
            'tagline' => $course->tagline,
            'description' => $course->description,
            'duration' => $course->duration,
            'certification' => $course->certification,
            'batchSize' => $course->batch_size,
            'rating' => $course->rating,
            'studentsTrained' => $course->students_trained,
            'jobPlacement' => $course->job_placement,
            'experienceYears' => $course->experience_years,

            'iconImage' => $course->icon_image
                ? asset('uploads/training/' . $course->icon_image)
                : null,

            'videoUrl' => $course->video_url,

            'benefitsTitle' => $course->benefits_title,
            'benefitsSubtitle' => $course->benefits_subtitle,

            'meta_title' => $course->meta_title,
            'meta_description' => $course->meta_description,
            'meta_keywords' => $course->meta_keywords,

            'modules' => $course->modules->map(function ($module) {
                return [
                    'id' => $module->id,
                    'day' => $module->day_name,
                    'title' => $module->title,
                    'items' => $module->items->pluck('item')->values()
                ];
            })->values(),

            'benefits' => $course->benefits->map(function ($benefit) {
                return [
                    'id' => $benefit->id,
                    'title' => $benefit->title,
                    'description' => $benefit->description,
                    'image' => $benefit->image
                        ? asset('uploads/training-benefits/' . $benefit->image)
                        : null,
                    'items' => $benefit->items->pluck('item')->values()
                ];
            })->values(),

            'highlights' => $course->highlights->map(function ($highlight) {
                return [
                    'id' => $highlight->id,
                    'title' => $highlight->title,
                    'description' => $highlight->description,
                    'items' => $highlight->items->pluck('item')->values()
                ];
            })->values(),

            'specializations' => $course->specializationPrograms->map(function ($specialization) {
                return [
                    'id' => $specialization->id,
                    'title' => $specialization->title,
                    'description' => $specialization->description,
                    'image' => $specialization->image
                        ? asset('uploads/specialization-programs/' . $specialization->image)
                        : null,
                    'items' => $specialization->items->pluck('item')->values()
                ];
            })->values(),
        ];
    }
}