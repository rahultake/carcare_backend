@extends('admin.layouts.app')

@section('content')

<div class="card">

<div class="card-header">

<h4>

Academy Highlights

<a href="{{ route('admin.academy-highlights.create') }}"
   class="btn btn-primary float-end">

Add Highlight

</a>

</h4>

</div>

<div class="card-body">

<table class="table table-bordered">

<thead>

<tr>
    <th>ID</th>
    <th>Course</th>
    <th>Title</th>
    <th>Description</th>
    <th>Action</th>
</tr>

</thead>

<tbody>

@foreach($highlights as $row)

<tr>

<td>{{ $row->id }}</td>

<td>{{ $row->course->name ?? '' }}</td>

<td>{{ $row->title }}</td>

<td>{{ Str::limit($row->description,50) }}</td>

<td>

<a href="{{ route('admin.academy-highlights.edit',$row->id) }}"
   class="btn btn-warning btn-sm">

Edit

</a>

<form method="POST"
      action="{{ route('admin.academy-highlights.destroy',$row->id) }}"
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