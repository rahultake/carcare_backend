@extends('admin.layouts.app')

@section('content')

<div class="card">

<div class="card-header">

<h4>

Specialization Items

<a href="{{ route('admin.specialization-items.create') }}"
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
    <th>Specialization</th>
    <th>Item</th>
    <th width="180">Action</th>
</tr>
</thead>

<tbody>

@foreach($items as $row)

<tr>

<td>{{ $row->id }}</td>

<td>
{{ $row->specialization->course->name ?? '' }}
</td>

<td>
{{ $row->specialization->title ?? '' }}
</td>

<td>
{{ $row->item }}
</td>

<td>

<a href="{{ route('admin.specialization-items.show',$row->id) }}"
   class="btn btn-info btn-sm">
View
</a>

<a href="{{ route('admin.specialization-items.edit',$row->id) }}"
   class="btn btn-warning btn-sm">
Edit
</a>

<form method="POST"
      action="{{ route('admin.specialization-items.destroy',$row->id) }}"
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