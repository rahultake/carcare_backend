@extends('admin.layouts.app')

@section('title', 'Brands')

@section('content')

<div class="container">

    <h2>Brands</h2>

    <a href="{{ route('admin.brands.create') }}"
       class="btn btn-primary mb-3">
        Add Brand
    </a>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <table class="table table-bordered">

        <thead>
            <tr>
                <th>#</th>
                <th>Image</th>
                <th>Name</th>
                <th>Slug</th>
                <th>Products</th>
                <th width="180">Action</th>
            </tr>
        </thead>

        <tbody>

        @forelse($brands as $brand)

            <tr>

                <td>{{ $brand->id }}</td>

                <td>
                    @if($brand->image)
                        <img src="{{ asset($brand->image) }}"
                             width="70">
                    @endif
                </td>

                <td>{{ $brand->name }}</td>

                <td>{{ $brand->slug }}</td>

                <td>{{ $brand->products()->count() }}</td>

                <td>

                    <a href="{{ route('admin.brands.edit',$brand->id) }}"
                       class="btn btn-warning btn-sm">
                        Edit
                    </a>

                    <form action="{{ route('admin.brands.destroy',$brand->id) }}"
                          method="POST"
                          style="display:inline">

                        @csrf
                        @method('DELETE')

                        <button type="submit"
                                class="btn btn-danger btn-sm"
                                onclick="return confirm('Delete this brand?')">
                            Delete
                        </button>

                    </form>

                </td>

            </tr>

        @empty

            <tr>
                <td colspan="6" class="text-center">
                    No brands found.
                </td>
            </tr>

        @endforelse

        </tbody>

    </table>

    {{ $brands->links() }}

</div>

@endsection