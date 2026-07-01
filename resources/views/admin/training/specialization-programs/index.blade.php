@extends('admin.layouts.app')

@section('content')

<div class="card">

<div class="card-header">

<h4>

Specialization Programs

<a href="{{ route('admin.specialization-programs.create') }}"
   class="btn btn-primary float-end">

Add Program

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
    <th>Image</th>
    <th>Action</th>
</tr>
</thead>

<tbody>

@foreach($programs as $row)

<tr>

<td>{{ $row->id }}</td>

<td>{{ $row->course->name ?? '' }}</td>

<td>{{ $row->title }}</td>

<td>

@if($row->image)

<img src="{{ asset('uploads/specialization-programs/'.$row->image) }}"
     width="80">

@endif

</td>

<td>

<a href="{{ route('admin.specialization-programs.show',$row->id) }}"
   class="btn btn-info btn-sm">

View

</a>

<a href="{{ route('admin.specialization-programs.edit',$row->id) }}"
   class="btn btn-warning btn-sm">

Edit

</a>

<form method="POST"
      action="{{ route('admin.specialization-programs.destroy',$row->id) }}"
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

{{ $programs->links() }}

</div>

</div>

@endsection