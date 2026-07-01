@extends('admin.layouts.app')

@section('content')

<form method="POST"
      action="{{ route('admin.training-benefits.update',$benefit->id) }}"
      enctype="multipart/form-data">

@csrf
@method('PUT')

@include('admin.training.benefits.form')

<button class="btn btn-primary">
Update
</button>

</form>

@endsection