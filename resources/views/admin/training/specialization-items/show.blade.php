@extends('admin.layouts.app')

@section('content')

<div class="card">

<div class="card-header">
Specialization Item Details
</div>

<div class="card-body">

<table class="table table-bordered">

<tr>
    <th width="200">Course</th>
    <td>{{ $item->specialization->course->name ?? '' }}</td>
</tr>

<tr>
    <th>Specialization</th>
    <td>{{ $item->specialization->title ?? '' }}</td>
</tr>

<tr>
    <th>Item</th>
    <td>{{ $item->item }}</td>
</tr>

<tr>
    <th>Created At</th>
    <td>{{ $item->created_at }}</td>
</tr>

</table>

</div>

</div>

@endsection