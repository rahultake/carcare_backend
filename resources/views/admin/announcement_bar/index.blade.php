@extends('admin.layouts.app')

@section('title','Announcement Bar')

@section('content')

<div class="container">

    <div class="card">
        <div class="card-header">
            <h4>Announcement Bar Settings</h4>
        </div>

        <div class="card-body">

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('admin.announcement-bar.update') }}"
                  method="POST">
                @csrf

                <div class="mb-3">
                    <label>Text Before</label>
                    <input type="text"
                           name="text_before"
                           class="form-control"
                           value="{{ old('text_before',$announcement->text_before) }}">
                </div>

                <div class="mb-3">
                    <label>Highlight Text</label>
                    <input type="text"
                           name="highlight_text"
                           class="form-control"
                           value="{{ old('highlight_text',$announcement->highlight_text) }}">
                </div>

                <div class="mb-3">
                    <label>Text After</label>
                    <input type="text"
                           name="text_after"
                           class="form-control"
                           value="{{ old('text_after',$announcement->text_after) }}">
                </div>

                <div class="mb-3">
                    <label>Button Text</label>
                    <input type="text"
                           name="button_text"
                           class="form-control"
                           value="{{ old('button_text',$announcement->button_text) }}">
                </div>

                <div class="mb-3">
                    <label>Button URL</label>
                    <input type="text"
                           name="button_url"
                           class="form-control"
                           value="{{ old('button_url',$announcement->button_url) }}">
                </div>

                <div class="mb-3">
                    <label>Status</label>

                    <select name="status" class="form-control">
                        <option value="active"
                            {{ $announcement->status == 'active' ? 'selected' : '' }}>
                            Active
                        </option>

                        <option value="inactive"
                            {{ $announcement->status == 'inactive' ? 'selected' : '' }}>
                            Inactive
                        </option>
                    </select>
                </div>

                <button class="btn btn-primary">
                    Save Changes
                </button>

            </form>

        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">
            Preview
        </div>

        <div class="card-body p-0">

            <div class="bg-primary text-white py-2 px-4 text-center">

                {{ $announcement->text_before }}

                <span class="text-danger fw-bold">
                    {{ $announcement->highlight_text }}
                </span>

                {{ $announcement->text_after }}

                <a href="{{ $announcement->button_url }}"
                   class="text-warning fw-bold">

                    {{ $announcement->button_text }}
                </a>

            </div>

        </div>
    </div>

</div>

@endsection