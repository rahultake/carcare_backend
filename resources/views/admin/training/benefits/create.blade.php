@extends('admin.layouts.app')

@section('content')

<form method="POST"
      action="{{ route('admin.training-benefits.store') }}"
      enctype="multipart/form-data">

@csrf

@include('admin.training.benefits.form')

<button class="btn btn-success">
Save
</button>

</form>

@endsection