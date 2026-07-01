<div class="row">

<div class="col-md-12 mb-3">

<label>Select Specialization Program</label>

<select name="specialization_id"
        class="form-control">

<option value="">
Select Program
</option>

@foreach($specializations as $specialization)

<option value="{{ $specialization->id }}"
{{ old('specialization_id',$item->specialization_id ?? '') == $specialization->id ? 'selected' : '' }}>

{{ $specialization->course->name }}
-
{{ $specialization->title }}

</option>

@endforeach

</select>

</div>

<div class="col-md-12 mb-3">

<label>Item</label>

<input type="text"
       name="item"
       class="form-control"
       value="{{ old('item',$item->item ?? '') }}"
       placeholder="Enter Item">

</div>

</div>