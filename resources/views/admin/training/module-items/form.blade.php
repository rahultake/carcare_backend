<div class="row">

<div class="col-md-12 mb-3">

<label>Select Module</label>

<select name="module_id"
        class="form-control">

<option value="">
Select Module
</option>

@foreach($modules as $module)

<option value="{{ $module->id }}"
{{ old('module_id',$item->module_id ?? '') == $module->id ? 'selected' : '' }}>

{{ $module->course->name }}
-
{{ $module->day_name }}
-
{{ $module->title }}

</option>

@endforeach

</select>

</div>

<div class="col-md-12 mb-3">

<label>Module Item</label>

<textarea
    name="item"
    rows="4"
    class="form-control">{{ old('item',$item->item ?? '') }}</textarea>

</div>

</div>