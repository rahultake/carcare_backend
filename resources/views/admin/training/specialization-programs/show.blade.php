@extends('admin.layouts.app')

@section('content')

<div class="card">

<div class="card-header">

{{ $program->title }}

</div>

<div class="card-body">

<p>
<strong>Course:</strong>
{{ $program->course->name }}
</p>

<p>
<strong>Title:</strong>
{{ $program->title }}
</p>

<p>
<strong>Description:</strong>
<br>
{!! nl2br($program->description) !!}
</p>

@if($program->image)

<img src="{{ asset('uploads/specialization-programs/'.$program->image) }}"
     width="200">

@endif

</div>

</div>

@endsection