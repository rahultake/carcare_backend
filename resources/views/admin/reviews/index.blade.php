@extends('admin.layouts.app')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Product Reviews</h4>

        <button
            class="btn btn-primary"
            data-bs-toggle="modal"
            data-bs-target="#addReviewModal">
            Add Review
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-body">

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Product</th>
                        <th>Rating</th>
                        <th>Review</th>
                        <th>Status</th>
                        <th width="220">Action</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($reviews as $review)

                        <tr>

                            <td>{{ $review->id }}</td>

                            <td>
                                {{ $review->user->name ?? 'N/A' }}
                            </td>

                            <td>
                                {{ $review->product->name ?? 'N/A' }}
                            </td>

                            <td>
                                {{ $review->rating }} ★
                            </td>

                            <td>
                                {{ $review->review }}
                            </td>

                            <td>

                                @if($review->status == 'published')

                                    <span class="badge bg-success">
                                        Published
                                    </span>

                                @else

                                    <span class="badge bg-danger">
                                        Unpublished
                                    </span>

                                @endif

                            </td>

                            <td>

                                <form
                                    action="{{ route('admin.reviews.toggle-status', $review->id) }}"
                                    method="POST"
                                    class="d-inline">

                                    @csrf

                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-warning">

                                        @if($review->status == 'published')
                                            Unpublish
                                        @else
                                            Publish
                                        @endif

                                    </button>
                                </form>

                                <form
                                    action="{{ route('admin.reviews.destroy', $review->id) }}"
                                    method="POST"
                                    class="d-inline">

                                    @csrf
                                    @method('DELETE')

                                    <button
                                        onclick="return confirm('Delete review?')"
                                        class="btn btn-sm btn-danger">

                                        Delete

                                    </button>
                                </form>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="7" class="text-center">
                                No reviews found
                            </td>
                        </tr>

                    @endforelse

                </tbody>
            </table>

        </div>
    </div>
</div>

<!-- ADD REVIEW MODAL -->

<div
    class="modal fade"
    id="addReviewModal"
    tabindex="-1">

    <div class="modal-dialog">

        <div class="modal-content">

            <form
                action="{{ route('admin.reviews.store') }}"
                method="POST">

                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">
                        Add Review
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                    </button>
                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <label>User</label>

                        <select
                            name="user_id"
                            class="form-control"
                            required>

                            <option value="">
                                Select User
                            </option>

                            @foreach($users as $user)

                                <option value="{{ $user->id }}">
                                    {{ $user->name }}
                                </option>

                            @endforeach

                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Product</label>

                        <select
                            name="product_id"
                            class="form-control"
                            required>

                            <option value="">
                                Select Product
                            </option>

                            @foreach($products as $product)

                                <option value="{{ $product->id }}">
                                    {{ $product->name }}
                                </option>

                            @endforeach

                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Rating</label>

                        <select
                            name="rating"
                            class="form-control"
                            required>

                            <option value="1">1 Star</option>
                            <option value="2">2 Star</option>
                            <option value="3">3 Star</option>
                            <option value="4">4 Star</option>
                            <option value="5">5 Star</option>

                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Review</label>

                        <textarea
                            name="review"
                            class="form-control"
                            rows="4"
                            required></textarea>
                    </div>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">

                        Close

                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary">

                        Save Review

                    </button>

                </div>

            </form>

        </div>
    </div>
</div>

@endsection