@extends('admin.layouts.app')

@section('content')

<div class="card">

<div class="card-header">
    {{ $course->name }}
</div>

<div class="card-body">

<p><strong>Duration :</strong> {{ $course->duration }}</p>

<p><strong>Certification :</strong> {{ $course->certification }}</p>

<p><strong>Batch Size :</strong> {{ $course->batch_size }}</p>

<p><strong>Rating :</strong> {{ $course->rating }}</p>

<p><strong>Students Trained :</strong> {{ $course->students_trained }}</p>

<p><strong>Job Placement :</strong> {{ $course->job_placement }}</p>

<p><strong>Status :</strong> {{ $course->status }}</p>

@if($course->icon_image)

<img src="{{ asset('uploads/training/'.$course->icon_image) }}"
     width="200">

@endif

</div>

</div>

@endsection