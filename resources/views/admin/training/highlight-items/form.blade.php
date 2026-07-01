<div class="row">

<div class="col-md-12 mb-3">

<label>Select Highlight</label>

<select name="highlight_id"
        class="form-control">

<option value="">
Select Highlight
</option>

@foreach($highlights as $highlight)

<option value="{{ $highlight->id }}"
{{ old('highlight_id',$item->highlight_id ?? '') == $highlight->id ? 'selected' : '' }}>

{{ $highlight->course->name }}
-
{{ $highlight->title }}

</option>

@endforeach

</select>

</div>

<div class="col-md-12 mb-3">

<label>Highlight Item</label>

<input type="text"
       name="item"
       class="form-control"
       value="{{ old('item',$item->item ?? '') }}"
       placeholder="Enter Highlight Item">

</div>

</div>