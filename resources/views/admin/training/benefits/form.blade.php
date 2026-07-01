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
{{ old('course_id',$benefit->course_id ?? '')==$id ? 'selected':'' }}>

{{ $name }}

</option>

@endforeach

</select>

</div>

<div class="col-md-6 mb-3">

<label>Benefit Title</label>

<input type="text"
       name="title"
       class="form-control"
       value="{{ old('title',$benefit->title ?? '') }}">

</div>

<div class="col-md-12 mb-3">

<label>Description</label>

<textarea name="description"
          rows="5"
          class="form-control">{{ old('description',$benefit->description ?? '') }}</textarea>

</div>

<div class="col-md-6 mb-3">

<label>Image</label>

<input type="file"
       name="image"
       class="form-control">

@if(!empty($benefit->image))

<br>

<img src="{{ asset('uploads/training-benefits/'.$benefit->image) }}"
     width="100">

@endif

</div>

</div>