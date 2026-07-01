<div class="row">

<div class="col-md-6 mb-3">

<label>Course</label>

<select name="course_id"
        class="form-control">

<option value="">
Select Course
</option>

@foreach($courses as $id => $name)

<option value="{{ $id }}"
{{ old('course_id',$module->course_id ?? '') == $id ? 'selected' : '' }}>

{{ $name }}

</option>

@endforeach

</select>

</div>

<div class="col-md-6 mb-3">

<label>Day Name</label>

<input type="text"
       name="day_name"
       class="form-control"
       value="{{ old('day_name',$module->day_name ?? '') }}"
       placeholder="Day 1">

</div>

<div class="col-md-12 mb-3">

<label>Module Title</label>

<input type="text"
       name="title"
       class="form-control"
       value="{{ old('title',$module->title ?? '') }}"
       placeholder="Introduction & Fundamentals">

</div>

</div>