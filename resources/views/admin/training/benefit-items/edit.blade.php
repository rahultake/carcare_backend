@extends('admin.layouts.app')

@section('content')

<div class="card">

<div class="card-header">
Edit Benefit Item
</div>

<div class="card-body">

<form method="POST"
      action="{{ route('admin.benefit-items.update',$item->id) }}">

@csrf
@method('PUT')

@include('admin.training.benefit-items.form')

<button class="btn btn-primary">
Update
</button>

</form>

</div>

</div>

@endsection