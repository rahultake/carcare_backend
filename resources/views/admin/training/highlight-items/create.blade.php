@extends('admin.layouts.app')

@section('content')

<div class="card">

<div class="card-header">
    Add Highlight Item
</div>

<div class="card-body">

<form method="POST"
      action="{{ route('admin.highlight-items.store') }}">

@csrf

@include('admin.training.highlight-items.form')

<button type="submit"
        class="btn btn-success">

    Save

</button>

</form>

</div>

</div>

@endsection