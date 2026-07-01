@extends('admin.layouts.app')

@section('content')

<form method="POST"
      enctype="multipart/form-data"
      action="{{ route('admin.specialization-programs.update',$program->id) }}">

@csrf
@method('PUT')

@include('admin.training.specialization-programs.form')

<button class="btn btn-primary">
Update
</button>

</form>

@endsection