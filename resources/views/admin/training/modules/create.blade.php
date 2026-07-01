@extends('admin.layouts.app')

@section('content')

<div class="card">

<div class="card-header">
    Add Course Module
</div>

<div class="card-body">

<form method="POST"
      action="{{ route('admin.course-modules.store') }}">

@csrf

@include('admin.training.modules.form')

<button class="btn btn-success">
    Save
</button>

</form>

</div>

</div>

@endsection