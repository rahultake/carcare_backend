@extends('admin.layouts.app')

@section('content')

<div class="card">

<div class="card-header">
Add Specialization Item
</div>

<div class="card-body">

<form method="POST"
      action="{{ route('admin.specialization-items.store') }}">

@csrf

@include('admin.training.specialization-items.form')

<button class="btn btn-success">
Save
</button>

</form>

</div>

</div>

@endsection