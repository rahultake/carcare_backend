@extends('admin.layouts.app')

@section('title', 'Blogs')
@section('page-title', 'Blogs')

@section('content')
<div class="container">
    <h2>Blogs</h2>
    <a href="{{ route('admin.blogs.create') }}" class="btn btn-primary mb-3">Add Blog</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Title</th>
                <th>Category</th>
                <th>Image</th>
                <th>Short Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($blogs as $blog)
                <tr>
                    <td>{{ $blog->title }}</td>
                    <td>{{ $blog->category->name ?? 'N/A' }}</td>
                    <td>
                        @if($blog->image)
                            <img src="{{ asset($blog->image) }}" width="80">
                        @endif
                    </td>
                    <td>{{ Str::limit($blog->short_description, 50) }}</td>
                    <td>
                        <a href="{{ route('admin.blogs.edit', $blog) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('admin.blogs.destroy', $blog) }}" method="POST" style="display:inline-block">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger"
                                onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">No blogs found.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $blogs->links() }}
</div>
@endsection
