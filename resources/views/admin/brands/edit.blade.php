@extends('admin.layouts.app')

@section('title', 'Edit Brand')

@section('content')

<div class="container">

    <h2>Edit Brand</h2>

    <form action="{{ route('admin.brands.update',$brand->id) }}"
          method="POST"
          enctype="multipart/form-data">

        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>Name *</label>
            <input type="text"
                   name="name"
                   class="form-control"
                   value="{{ old('name',$brand->name) }}"
                   required>
        </div>

        <div class="mb-3">
            <label>Current Image</label><br>

            @if($brand->image)
                <img src="{{ asset($brand->image) }}"
                     width="120">
            @endif
        </div>

        <div class="mb-3">
            <label>Image</label>
            <input type="file" name="image" class="form-control">
        </div>

        <div class="mb-3">
            <label>Meta Title</label>
            <input type="text"
                   name="meta_title"
                   value="{{ old('meta_title',$brand->meta_title) }}"
                   class="form-control">
        </div>

        <div class="mb-3">
            <label>Meta Description</label>

            <textarea name="meta_description"
                      rows="4"
                      class="form-control">{{ old('meta_description',$brand->meta_description) }}</textarea>
        </div>

        <button class="btn btn-primary">
            Update
        </button>

    </form>

</div>

@endsection