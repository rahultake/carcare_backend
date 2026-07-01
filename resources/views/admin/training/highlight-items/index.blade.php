@extends('admin.layouts.app')

@section('content')

<div class="card">

<div class="card-header">

    <h4>

        Highlight Items

        <a href="{{ route('admin.highlight-items.create') }}"
           class="btn btn-primary float-end">

            Add Highlight Item

        </a>

    </h4>

</div>

<div class="card-body">

<table class="table table-bordered">

<thead>

<tr>
    <th>ID</th>
    <th>Course</th>
    <th>Highlight</th>
    <th>Item</th>
    <th width="180">Action</th>
</tr>

</thead>

<tbody>

@foreach($items as $row)

<tr>

<td>{{ $row->id }}</td>

<td>
    {{ $row->highlight->course->name ?? '' }}
</td>

<td>
    {{ $row->highlight->title ?? '' }}
</td>

<td>
    {{ $row->item }}
</td>

<td>

    <a href="{{ route('admin.highlight-items.show',$row->id) }}"
       class="btn btn-info btn-sm">
        View
    </a>

    <a href="{{ route('admin.highlight-items.edit',$row->id) }}"
       class="btn btn-warning btn-sm">
        Edit
    </a>

    <form method="POST"
          action="{{ route('admin.highlight-items.destroy',$row->id) }}"
          style="display:inline-block">

        @csrf
        @method('DELETE')

        <button class="btn btn-danger btn-sm"
        onclick="return confirm('Delete Item?')">

            Delete

        </button>

    </form>

</td>

</tr>

@endforeach

</tbody>

</table>

{{ $items->links() }}

</div>

</div>

@endsection