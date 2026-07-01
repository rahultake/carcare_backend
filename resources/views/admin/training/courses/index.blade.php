@extends('admin.layouts.app')

@section('title', 'Training Courses')
@section('page-title', 'Training Courses')

@section('content')

<div class="container">

    <div class="d-flex justify-content-between mb-3">

        <h3>Training Courses</h3>

        <a href="{{ route('admin.training-courses.create') }}"
           class="btn btn-primary">
            Add Course
        </a>

    </div>

    <table class="table table-bordered">

        <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Duration</th>
            <th>Certification</th>
            <th>Status</th>
            <th width="200">Action</th>
        </tr>
        </thead>

        <tbody>

        @foreach($courses as $course)

        <tr>

            <td>{{ $course->id }}</td>

            <td>{{ $course->name }}</td>

            <td>{{ $course->duration }}</td>

            <td>{{ $course->certification }}</td>

            <td>{{ $course->status }}</td>

            <td>

                <a href="{{ route('admin.training-courses.show',$course->id) }}"
                   class="btn btn-info btn-sm">
                   View
                </a>

                <a href="{{ route('admin.training-courses.edit',$course->id) }}"
                   class="btn btn-warning btn-sm">
                   Edit
                </a>

                <form method="POST"
                      action="{{ route('admin.training-courses.destroy',$course->id) }}"
                      style="display:inline">

                    @csrf
                    @method('DELETE')

                    <button class="btn btn-danger btn-sm"
                        onclick="return confirm('Delete?')">
                        Delete
                    </button>

                </form>

            </td>

        </tr>

        @endforeach

        </tbody>

    </table>

    {{ $courses->links() }}

</div>

@endsection