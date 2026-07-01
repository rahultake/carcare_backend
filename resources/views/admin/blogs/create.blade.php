@extends('admin.layouts.app')

@section('title', 'Blogs')
@section('page-title', 'Blogs')

@section('content')
<div class="container">
    <h2>Create Blog</h2>

    <form action="{{ route('admin.blogs.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="form-group">
            <label>Title *</label>
            <input type="text" name="title" class="form-control" required>
        </div>

        <div class="form-group">
            <label>Category *</label>
            <select name="category_id" class="form-control" required>
                <option value="">Select Category</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label>Short Description</label>
            <textarea name="short_description" class="form-control"></textarea>
        </div>

        <div class="form-group">
            <label>Long Description</label>
            <textarea name="long_description" id="description" class="form-control" rows="6"></textarea>
        </div>

        <div class="form-group">
            <label>Image</label>
            <input type="file" name="image" class="form-control-file">
        </div>

        <button type="submit" class="btn btn-success">Save</button>
        <a href="{{ route('admin.blogs.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
