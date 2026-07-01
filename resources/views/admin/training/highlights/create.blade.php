@extends('admin.layouts.app')

@section('content')

<div class="card">

<div class="card-header">
Add Academy Highlight
</div>

<div class="card-body">

<form method="POST"
      action="{{ route('admin.academy-highlights.store') }}">

@csrf

@include('admin.training.highlights.form')

<button class="btn btn-success">
Save
</button>

</form>

</div>

</div>

@endsection