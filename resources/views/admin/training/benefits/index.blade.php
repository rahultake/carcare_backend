@extends('admin.layouts.app')

@section('content')

<div class="card">

<div class="card-header">

<h4>
Training Benefits

<a href="{{ route('admin.training-benefits.create') }}"
   class="btn btn-primary float-end">

Add Benefit

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

@foreach($benefits as $row)

<tr>

<td>{{ $row->id }}</td>

<td>{{ $row->course->name ?? '' }}</td>

<td>{{ $row->title }}</td>

<td>

@if($row->image)

<img src="{{ asset('uploads/training-benefits/'.$row->image) }}"
     width="80">

@endif

</td>

<td>

<a href="{{ route('admin.training-benefits.edit',$row->id) }}"
   class="btn btn-warning btn-sm">

Edit

</a>

<form action="{{ route('admin.training-benefits.destroy',$row->id) }}"
      method="POST"
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