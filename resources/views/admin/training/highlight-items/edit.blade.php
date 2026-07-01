@extends('admin.layouts.app')

@section('content')

<div class="card">

<div class="card-header">
    Edit Highlight Item
</div>

<div class="card-body">

<form method="POST"
      action="{{ route('admin.highlight-items.update',$item->id) }}">

@csrf
@method('PUT')

@include('admin.training.highlight-items.form')

<button type="submit"
        class="btn btn-primary">

    Update

</button>

</form>

</div>

</div>

@endsection