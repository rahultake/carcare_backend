@extends('admin.layouts.app')

@section('content')

<div class="container">

<form method="POST"
      enctype="multipart/form-data"
      action="{{ route('admin.training-courses.update',$course->id) }}">

@csrf
@method('PUT')

@include('admin.training.courses.form')

<button type="submit" class="btn btn-primary">
Update Course
</button>

</form>

</div>

@endsection