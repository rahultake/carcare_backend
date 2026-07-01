@extends('admin.layouts.app')

@section('content')

<div class="card">

<div class="card-header">
    Edit Module Item
</div>

<div class="card-body">

<form method="POST"
      action="{{ route('admin.module-items.update',$item->id) }}">

@csrf
@method('PUT')

@include('admin.training.module-items.form')

<button class="btn btn-primary">
    Update
</button>

</form>

</div>

</div>

@endsection