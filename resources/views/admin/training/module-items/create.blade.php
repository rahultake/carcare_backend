@extends('admin.layouts.app')

@section('content')

<div class="card">

<div class="card-header">
    Add Module Item
</div>

<div class="card-body">

<form method="POST"
      action="{{ route('admin.module-items.store') }}">

@csrf

@include('admin.training.module-items.form')

<button class="btn btn-success">
    Save
</button>

</form>

</div>

</div>

@endsection