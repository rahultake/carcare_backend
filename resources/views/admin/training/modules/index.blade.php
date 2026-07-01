@extends('admin.layouts.app')

@section('content')

<div class="card">

<div class="card-header">

    <h4>
        Course Modules

        <a href="{{ route('admin.course-modules.create') }}"
           class="btn btn-primary float-end">
            Add Module
        </a>
    </h4>

</div>

<div class="card-body">

<table class="table table-bordered">

<thead>

<tr>
    <th>ID</th>
    <th>Course</th>
    <th>Day Name</th>
    <th>Title</th>
    <th width="150">Action</th>
</tr>

</thead>

<tbody>

@foreach($modules as $module)

<tr>

    <td>{{ $module->id }}</td>

    <td>{{ $module->course->name ?? '' }}</td>

    <td>{{ $module->day_name }}</td>

    <td>{{ $module->title }}</td>

    <td>

        <a href="{{ route('admin.course-modules.edit',$module->id) }}"
           class="btn btn-warning btn-sm">
            Edit
        </a>

        <form action="{{ route('admin.course-modules.destroy',$module->id) }}"
              method="POST"
              style="display:inline-block">

            @csrf
            @method('DELETE')

            <button type="submit"
                    onclick="return confirm('Delete?')"
                    class="btn btn-danger btn-sm">

                Delete

            </button>

        </form>

    </td>

</tr>

@endforeach

</tbody>

</table>

</div>

</div>

@endsection