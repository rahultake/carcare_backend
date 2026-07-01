<div class="row">

<div class="col-md-6 mb-3">

<label>Course</label>

<select name="course_id"
        class="form-control">

<option value="">
Select Course
</option>

@foreach($courses as $id=>$name)

<option value="{{ $id }}"
{{ old('course_id',$highlight->course_id ?? '') == $id ? 'selected' : '' }}>

{{ $name }}

</option>

@endforeach

</select>

</div>

<div class="col-md-12 mb-3">

<label>Highlight Title</label>

<input type="text"
       name="title"
       class="form-control"
       value="{{ old('title',$highlight->title ?? '') }}">

</div>

<div class="col-md-12 mb-3">

<label>Description</label>

<textarea name="description"
          rows="5"
          class="form-control">{{ old('description',$highlight->description ?? '') }}</textarea>

</div>

</div>