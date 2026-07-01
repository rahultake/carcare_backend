@extends('admin.layouts.app')

@section('title', 'Customers')
@section('page-title', 'Customers')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h5>Customers</h5>
        <input type="text" id="searchInput" class="form-control w-25" placeholder="Search customers">
    </div>

    <div class="card-body table-responsive">
        <table class="table table-striped" id="dataTable">
            <thead class="table-dark">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Joined</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($customers as $customer)
                <tr>
                    <td>{{ $customer->name }}</td>
                    <td>{{ $customer->email }}</td>
                    <td>{{ $customer->created_at->format('d M Y') }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.customers.destroy', $customer->id) }}"
                              onsubmit="return confirm('Delete this customer?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div id="paginationInfo" class="text-muted small"></div>
            <nav>
                <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
            </nav>
        </div>
    </div>
</div>
@endsection
<script>
document.addEventListener('DOMContentLoaded', function () {

    const searchInput = document.getElementById('searchInput');
    const table = document.getElementById('dataTable');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));

    const rowsPerPage = 10;
    let currentPage = 1;
    let filteredRows = [...rows];

    function renderTable() {
        tbody.innerHTML = '';

        let start = (currentPage - 1) * rowsPerPage;
        let end = start + rowsPerPage;

        filteredRows.slice(start, end).forEach(row => {
            tbody.appendChild(row);
        });

        updatePagination();
        updateInfo();
    }

    function updatePagination() {
        const pagination = document.getElementById('pagination');
        pagination.innerHTML = '';

        const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
        if (totalPages <= 1) return;

        // Previous
        pagination.innerHTML += `
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage - 1}">Prev</a>
            </li>
        `;

        for (let i = 1; i <= totalPages; i++) {
            pagination.innerHTML += `
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `;
        }

        // Next
        pagination.innerHTML += `
            <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage + 1}">Next</a>
            </li>
        `;

        pagination.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const page = parseInt(this.dataset.page);
                if (!isNaN(page) && page >= 1 && page <= totalPages) {
                    currentPage = page;
                    renderTable();
                }
            });
        });
    }

    function updateInfo() {
        document.getElementById('paginationInfo').innerText =
            `Showing ${filteredRows.length} orders`;
    }

    searchInput.addEventListener('keyup', function () {
        const value = this.value.toLowerCase();
        filteredRows = rows.filter(row =>
            row.innerText.toLowerCase().includes(value)
        );
        currentPage = 1;
        renderTable();
    });

    renderTable();
});
</script>