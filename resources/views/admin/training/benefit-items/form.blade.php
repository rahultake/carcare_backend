<div class="row">

<div class="col-md-12 mb-3">

<label>Select Benefit</label>

<select name="benefit_id"
        class="form-control">

<option value="">
Select Benefit
</option>

@foreach($benefits as $benefit)

<option value="{{ $benefit->id }}"
{{ old('benefit_id',$item->benefit_id ?? '') == $benefit->id ? 'selected' : '' }}>

{{ $benefit->course->name }}
-
{{ $benefit->title }}

</option>

@endforeach

</select>

</div>

<div class="col-md-12 mb-3">

<label>Benefit Item</label>

<input type="text"
       name="item"
       class="form-control"
       value="{{ old('item',$item->item ?? '') }}"
       placeholder="Enter Benefit Item">

</div>

</div>