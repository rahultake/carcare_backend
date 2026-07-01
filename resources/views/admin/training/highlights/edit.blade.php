@extends('admin.layouts.app')

@section('content')

<div class="card">

<div class="card-header">
Edit Academy Highlight
</div>

<div class="card-body">

<form method="POST"
      action="{{ route('admin.academy-highlights.update',$highlight->id) }}">

@csrf
@method('PUT')

@include('admin.training.highlights.form')

<button class="btn btn-primary">
Update
</button>

</form>

</div>

</div>

@endsection