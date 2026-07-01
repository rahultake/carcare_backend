@extends('admin.layouts.app')

@section('content')

<div class="card">

<div class="card-header">

    <h4>

        Module Items

        <a href="{{ route('admin.module-items.create') }}"
           class="btn btn-primary float-end">

            Add Item

        </a>

    </h4>

</div>

<div class="card-body">

<table class="table table-bordered">

<thead>

<tr>
    <th>ID</th>
    <th>Course</th>
    <th>Module</th>
    <th>Item</th>
    <th width="150">Action</th>
</tr>

</thead>

<tbody>

@foreach($items as $row)

<tr>

    <td>{{ $row->id }}</td>

    <td>
        {{ $row->module->course->name ?? '' }}
    </td>

    <td>
        {{ $row->module->title ?? '' }}
    </td>

    <td>
        {{ $row->item }}
    </td>

    <td>

        <a href="{{ route('admin.module-items.edit',$row->id) }}"
           class="btn btn-warning btn-sm">

            Edit

        </a>

        <form action="{{ route('admin.module-items.destroy',$row->id) }}"
              method="POST"
              style="display:inline-block">

            @csrf
            @method('DELETE')

            <button
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