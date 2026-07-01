@extends('admin.layouts.app')

@section('content')

<div class="card">

<div class="card-header">
Add Benefit Item
</div>

<div class="card-body">

<form method="POST"
      action="{{ route('admin.benefit-items.store') }}">

@csrf

@include('admin.training.benefit-items.form')

<button class="btn btn-success">
Save
</button>

</form>

</div>

</div>

@endsection