@extends('admin.layouts.app')

@section('content')

<div class="container">

<form method="POST"
      enctype="multipart/form-data"
      action="{{ route('admin.training-courses.store') }}">

@csrf

@include('admin.training.courses.form')

<button type="submit" class="btn btn-success">
Save Course
</button>

</form>

</div>

@endsection