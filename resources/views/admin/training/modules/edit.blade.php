@extends('admin.layouts.app')

@section('content')

<div class="card">

<div class="card-header">
    Edit Course Module
</div>

<div class="card-body">

<form method="POST"
      action="{{ route('admin.course-modules.update',$module->id) }}">

@csrf
@method('PUT')

@include('admin.training.modules.form')

<button class="btn btn-primary">
    Update
</button>

</form>

</div>

</div>

@endsection