@extends('admin.layouts.app')

@section('title', 'Blogs')
@section('page-title', 'Blogs')

@section('content')
<div class="container">
    <h2>Edit Blog</h2>

    <form action="{{ route('admin.blogs.update', $blog) }}" method="POST" enctype="multipart/form-data">
        @csrf @method('PUT')

        <div class="form-group">
            <label>Title *</label>
            <input type="text" name="title" class="form-control" value="{{ $blog->title }}" required>
        </div>

        <div class="form-group">
            <label>Category *</label>
            <select name="category_id" class="form-control" required>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ $cat->id == $blog->category_id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label>Short Description</label>
            <textarea name="short_description" class="form-control">{{ $blog->short_description }}</textarea>
        </div>

        <div class="form-group">
            <label>Long Description</label>
            <textarea name="long_description" id="description" class="form-control" rows="6">{{ $blog->long_description }}</textarea>
        </div>

        <div class="form-group">
            <label>Image</label><br>
            @if($blog->image)
                <img src="{{ asset($blog->image) }}" width="100" class="mb-2">
            @endif
            <input type="file" name="image" class="form-control-file">
        </div>

        <button type="submit" class="btn btn-success">Update</button>
        <a href="{{ route('admin.blogs.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
