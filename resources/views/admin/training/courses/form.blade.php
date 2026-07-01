<div class="row">

<div class="col-md-6 mb-3">
<label>Course Name</label>
<input type="text"
       name="name"
       value="{{ old('name',$course->name ?? '') }}"
       class="form-control">
</div>

<div class="col-md-6 mb-3">
<label>Duration</label>
<input type="text"
       name="duration"
       value="{{ old('duration',$course->duration ?? '') }}"
       class="form-control">
</div>

<div class="col-md-6 mb-3">
<label>Certification</label>
<input type="text"
       name="certification"
       value="{{ old('certification',$course->certification ?? '') }}"
       class="form-control">
</div>

<div class="col-md-6 mb-3">
<label>Batch Size</label>
<input type="text"
       name="batch_size"
       value="{{ old('batch_size',$course->batch_size ?? '') }}"
       class="form-control">
</div>

<div class="col-md-12 mb-3">
<label>Tagline</label>
<textarea name="tagline"
          class="form-control">{{ old('tagline',$course->tagline ?? '') }}</textarea>
</div>

<div class="col-md-6 mb-3">
<label>Rating</label>
<input type="text"
       name="rating"
       value="{{ old('rating',$course->rating ?? '') }}"
       class="form-control">
</div>

<div class="col-md-6 mb-3">
<label>Students Trained</label>
<input type="text"
       name="students_trained"
       value="{{ old('students_trained',$course->students_trained ?? '') }}"
       class="form-control">
</div>

<div class="col-md-6 mb-3">
<label>Job Placement</label>
<input type="text"
       name="job_placement"
       value="{{ old('job_placement',$course->job_placement ?? '') }}"
       class="form-control">
</div>

<div class="col-md-6 mb-3">
<label>Experience Years</label>
<input type="text"
       name="experience_years"
       value="{{ old('experience_years',$course->experience_years ?? '') }}"
       class="form-control">
</div>

<div class="col-md-6 mb-3">
<label>Video URL</label>
<input type="text"
       name="video_url"
       value="{{ old('video_url',$course->video_url ?? '') }}"
       class="form-control">
</div>

<div class="col-md-6 mb-3">
<label>Icon Image</label>
<input type="file"
       name="icon_image"
       class="form-control">
</div>

<div class="col-md-12 mb-3">
<label>Benefits Title</label>
<input type="text"
       name="benefits_title"
       value="{{ old('benefits_title',$course->benefits_title ?? '') }}"
       class="form-control">
</div>

<div class="col-md-12 mb-3">
<label>Benefits Subtitle</label>
<textarea name="benefits_subtitle"
          class="form-control">{{ old('benefits_subtitle',$course->benefits_subtitle ?? '') }}</textarea>
</div>

<div class="col-md-12 mb-3">
<label>Meta Title</label>
<input type="text"
       name="meta_title"
       value="{{ old('meta_title',$course->meta_title ?? '') }}"
       class="form-control">
</div>

<div class="col-md-12 mb-3">
<label>Meta Description</label>
<textarea name="meta_description"
          class="form-control">{{ old('meta_description',$course->meta_description ?? '') }}</textarea>
</div>

<div class="col-md-12 mb-3">
<label>Meta Keywords</label>
<textarea name="meta_keywords"
          class="form-control">{{ old('meta_keywords',$course->meta_keywords ?? '') }}</textarea>
</div>

<div class="col-md-6 mb-3">
<label>Status</label>

<select name="status" class="form-control">

<option value="active"
{{ (old('status',$course->status ?? '')=='active') ? 'selected':'' }}>
Active
</option>

<option value="inactive"
{{ (old('status',$course->status ?? '')=='inactive') ? 'selected':'' }}>
Inactive
</option>

</select>

</div>

</div>