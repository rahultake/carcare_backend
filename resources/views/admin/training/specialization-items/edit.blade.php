@extends('admin.layouts.app')

@section('content')

<div class="card">

<div class="card-header">
Edit Specialization Item
</div>

<div class="card-body">

<form method="POST"
      action="{{ route('admin.specialization-items.update',$item->id) }}">

@csrf
@method('PUT')

@include('admin.training.specialization-items.form')

<button class="btn btn-primary">
Update
</button>

</form>

</div>

</div>

@endsection