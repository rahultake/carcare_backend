@extends('admin.layouts.app')

@section('content')

<div class="card">

<div class="card-header">

<h4>

Benefit Items

<a href="{{ route('admin.benefit-items.create') }}"
   class="btn btn-primary float-end">

Add Benefit Item

</a>

</h4>

</div>

<div class="card-body">

<table class="table table-bordered">

<thead>

<tr>
    <th>ID</th>
    <th>Course</th>
    <th>Benefit</th>
    <th>Item</th>
    <th>Action</th>
</tr>

</thead>

<tbody>

@foreach($items as $row)

<tr>

<td>{{ $row->id }}</td>

<td>
{{ $row->benefit->course->name ?? '' }}
</td>

<td>
{{ $row->benefit->title ?? '' }}
</td>

<td>
{{ $row->item }}
</td>

<td>

<a href="{{ route('admin.benefit-items.edit',$row->id) }}"
   class="btn btn-warning btn-sm">

Edit

</a>

<form method="POST"
      action="{{ route('admin.benefit-items.destroy',$row->id) }}"
      style="display:inline-block">

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

</div>

</div>

@endsection