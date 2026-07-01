@extends('admin.layouts.app')

@section('content')

<form method="POST"
      enctype="multipart/form-data"
      action="{{ route('admin.specialization-programs.store') }}">

@csrf

@include('admin.training.specialization-programs.form')

<button class="btn btn-success">
Save
</button>

</form>

@endsection