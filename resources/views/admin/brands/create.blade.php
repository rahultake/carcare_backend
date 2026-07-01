@extends('admin.layouts.app')

@section('title', 'Create Brand')

@section('content')

<div class="container">

    <h2>Create Brand</h2>

    <form action="{{ route('admin.brands.store') }}" method="POST" enctype="multipart/form-data">

        @csrf

        <div class="mb-3">
            <label>Name *</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Image</label>
            <input type="file" name="image" class="form-control">
        </div>

        <div class="mb-3">
            <label>Meta Title</label>
            <input type="text" name="meta_title" class="form-control">
        </div>

        <div class="mb-3">
            <label>Meta Description</label>
            <textarea name="meta_description" rows="4" class="form-control"></textarea>
        </div>

        <button class="btn btn-success">
            Save
        </button>

        <a href="{{ route('admin.brands.index') }}" class="btn btn-secondary">
            Cancel
        </a>

    </form>

</div>

@endsection