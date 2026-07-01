@extends('admin.layouts.app')

@section('content')

<div class="card">

    <div class="card-header">

        <h4>
            Training Inquiries

            <span class="badge bg-primary">
                {{ $inquiries->total() }}
            </span>
        </h4>

    </div>

    <div class="card-body">

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="table-responsive">

            <table class="table table-bordered table-striped">

                <thead>

                    <tr>
                        <th width="60">#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>City</th>
                        <th>State</th>
                        <th>Course</th>
                        <th>Message</th>
                        <th>Date</th>
                        <th width="100">Action</th>
                    </tr>

                </thead>

                <tbody>

                @forelse($inquiries as $key => $row)

                    <tr>

                        <td>
                            {{ $inquiries->firstItem() + $key }}
                        </td>

                        <td>
                            {{ $row->full_name }}
                        </td>

                        <td>
                            {{ $row->email }}
                        </td>

                        <td>
                            {{ $row->phone_number }}
                        </td>

                        <td>
                            {{ $row->city }}
                        </td>

                        <td>
                            {{ $row->state }}
                        </td>

                        <td>
                            {{ $row->course_interest }}
                        </td>

                        <td style="max-width:250px">
                            {{ \Illuminate\Support\Str::limit($row->message,80) }}
                        </td>

                        <td>
                            {{ $row->created_at->format('d M Y h:i A') }}
                        </td>

                        <td>

                            <button
                                class="btn btn-info btn-sm"
                                data-bs-toggle="modal"
                                data-bs-target="#viewModal{{ $row->id }}">
                                View
                            </button>

                            <form
                                action="{{ route('admin.training-inquiries.destroy',$row->id) }}"
                                method="POST"
                                style="display:inline-block">

                                @csrf
                                @method('DELETE')

                                <button
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Delete this inquiry?')">

                                    Delete

                                </button>

                            </form>

                        </td>

                    </tr>

                    <!-- Modal -->

                    <div class="modal fade"
                         id="viewModal{{ $row->id }}"
                         tabindex="-1">

                        <div class="modal-dialog modal-lg">

                            <div class="modal-content">

                                <div class="modal-header">

                                    <h5 class="modal-title">
                                        Inquiry Details
                                    </h5>

                                    <button type="button"
                                            class="btn-close"
                                            data-bs-dismiss="modal">
                                    </button>

                                </div>

                                <div class="modal-body">

                                    <table class="table table-bordered">

                                        <tr>
                                            <th width="200">Name</th>
                                            <td>{{ $row->full_name }}</td>
                                        </tr>

                                        <tr>
                                            <th>Email</th>
                                            <td>{{ $row->email }}</td>
                                        </tr>

                                        <tr>
                                            <th>Phone</th>
                                            <td>{{ $row->phone_number }}</td>
                                        </tr>

                                        <tr>
                                            <th>City</th>
                                            <td>{{ $row->city }}</td>
                                        </tr>

                                        <tr>
                                            <th>State</th>
                                            <td>{{ $row->state }}</td>
                                        </tr>

                                        <tr>
                                            <th>Course Interest</th>
                                            <td>{{ $row->course_interest }}</td>
                                        </tr>

                                        <tr>
                                            <th>Message</th>
                                            <td>{{ $row->message }}</td>
                                        </tr>

                                        <tr>
                                            <th>IP Address</th>
                                            <td>
                                                {{ $row->metadata['ip_address'] ?? '-' }}
                                            </td>
                                        </tr>

                                        <tr>
                                            <th>Submitted At</th>
                                            <td>
                                                {{ $row->created_at }}
                                            </td>
                                        </tr>

                                    </table>

                                </div>

                            </div>

                        </div>

                    </div>

                @empty

                    <tr>
                        <td colspan="10" class="text-center">
                            No inquiries found.
                        </td>
                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

        {{ $inquiries->links() }}

    </div>

</div>

@endsection