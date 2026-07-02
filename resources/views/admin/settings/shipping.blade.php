@extends('admin.layouts.app')

@section('title', 'Shipping Settings')
@section('page-title', 'Shipping Settings')

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i><strong>Validation Errors:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Main Configuration Form -->
<form action="{{ route('admin.shipping-settings.update') }}" method="POST" id="main-settings-form">
    @csrf
    <div class="row g-4">
        <!-- Main Provider Selection -->
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="card-title mb-3 fw-bold text-dark"><i class="fas fa-shipping-fast me-2 text-primary"></i>Active Shipping Provider</h5>
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <label for="shipping_provider" class="form-label">Choose Active Provider</label>
                            <select name="shipping_provider" id="shipping_provider" class="form-select form-select-lg">
                                <option value="parcelx" {{ setting('shipping_provider', 'parcelx') === 'parcelx' ? 'selected' : '' }}>ParcelX (Default)</option>
                                <option value="shiprocket" {{ setting('shipping_provider') === 'shiprocket' ? 'selected' : '' }}>Shiprocket</option>
                            </select>
                            <div class="form-text">All prepaid checkout orders will be booked with this provider automatically.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shiprocket Settings Card -->
        <div class="col-12 shipping-provider-card" id="provider-card-shiprocket" style="display: none;">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold text-dark"><i class="fas fa-rocket me-2 text-info"></i>Shiprocket API Configuration</h5>
                    <hr class="mt-3 mb-0">
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">API Account Email</label>
                            <input type="email" name="shiprocket_email" class="form-control" value="{{ setting('shiprocket_email') }}" placeholder="e.g. admin@yourstore.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">API Account Password</label>
                            <input type="password" name="shiprocket_password" class="form-control" value="{{ setting('shiprocket_password') }}" placeholder="••••••••">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">API Base URL</label>
                            <input type="text" name="shiprocket_base_url" class="form-control" value="{{ setting('shiprocket_base_url', 'https://apiv2.shiprocket.in/v1/external') }}" placeholder="https://apiv2.shiprocket.in/v1/external">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Pickup Location Name</label>
                            <input type="text" name="shiprocket_pickup_location" class="form-control" value="{{ setting('shiprocket_pickup_location', 'work') }}" placeholder="e.g. Primary Warehouse">
                            <div class="form-text">Must match the exact pickup nickname defined in your Shiprocket panel.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ParcelX Settings Card -->
        <div class="col-12 shipping-provider-card" id="provider-card-parcelx" style="display: none;">
            <div class="row g-4">
                <!-- API Credentials Card -->
                <div class="col-lg-7 col-md-12">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold text-dark mb-0"><i class="fas fa-key me-2 text-warning"></i>ParcelX API Credentials</h5>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="edit-credentials-lock-btn">
                                <i class="fas fa-lock me-1"></i> Edit Keys
                            </button>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Access Key</label>
                                    <input type="text" name="parcelx_access_key" id="parcelx_access_key" class="form-control" value="{{ setting('parcelx_access_key') }}" placeholder="Enter Access Key" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Secret Key</label>
                                    <input type="text" name="parcelx_secret_key" id="parcelx_secret_key" class="form-control" value="{{ setting('parcelx_secret_key') }}" placeholder="Enter Secret Key" readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Access Token</label>
                                    <textarea name="parcelx_access_token" id="parcelx_access_token" rows="3" class="form-control" placeholder="Paste Base64 Access Token from Settings -> API Docs" readonly>{{ setting('parcelx_access_token') }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">API Base URL</label>
                                    <input type="text" name="parcelx_base_url" id="parcelx_base_url" class="form-control" value="{{ setting('parcelx_base_url', 'https://app.parcelx.in') }}" placeholder="https://app.parcelx.in" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Active Pickup Address (Warehouse)</label>
                                    @if(!empty($parcelxWarehouses))
                                        <select name="parcelx_pickup_location" class="form-select">
                                            <option value="">-- Select Pickup Address --</option>
                                            @foreach($parcelxWarehouses as $wh)
                                                <option value="{{ $wh['warehouse_id'] }}" {{ (string)setting('parcelx_pickup_location') === (string)$wh['warehouse_id'] ? 'selected' : '' }}>
                                                    {{ $wh['title'] }} ({{ $wh['addressee'] }} - {{ $wh['pincode'] }})
                                                </option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input type="text" name="parcelx_pickup_location" class="form-control" value="{{ setting('parcelx_pickup_location') }}" placeholder="Enter Warehouse ID (e.g. 83474)">
                                        <div class="form-text text-danger mt-1 small"><i class="fas fa-exclamation-triangle"></i> No warehouses found. Add one on the right to populate options.</div>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Courier Routing Mode</label>
                                    <select name="parcelx_courier_type" class="form-select">
                                        <option value="1" {{ setting('parcelx_courier_type', '1') == '1' ? 'selected' : '' }}>Manual Courier Selection (Fallback)</option>
                                        <option value="0" {{ setting('parcelx_courier_type') == '0' ? 'selected' : '' }}>Priority (Cheapest Auto-Selection)</option>
                                    </select>
                                    <div class="form-text">Choose how ParcelX selects the delivery partner.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Default Courier Partner</label>
                                    <select name="parcelx_default_courier" class="form-select">
                                        <option value="PXDEL01" {{ setting('parcelx_default_courier', 'PXDEL01') === 'PXDEL01' ? 'selected' : '' }}>Delhivery (PXDEL01)</option>
                                        <option value="PXA01" {{ setting('parcelx_default_courier') === 'PXA01' ? 'selected' : '' }}>Amazon (PXA01)</option>
                                        <option value="PXBDE01" {{ setting('parcelx_default_courier') === 'PXBDE01' ? 'selected' : '' }}>Bluedart (PXBDE01)</option>
                                        <option value="PXXBS02" {{ setting('parcelx_default_courier') === 'PXXBS02' ? 'selected' : '' }}>XpressBees (PXXBS02)</option>
                                        <option value="PXEK02" {{ setting('parcelx_default_courier') === 'PXEK02' ? 'selected' : '' }}>Ekart (PXEK02)</option>
                                        <option value="PXGATI01" {{ setting('parcelx_default_courier') === 'PXGATI01' ? 'selected' : '' }}>Gati (PXGATI01)</option>
                                        <option value="PXSMUTI01" {{ setting('parcelx_default_courier') === 'PXSMUTI01' ? 'selected' : '' }}>Shree Maruti (PXSMUTI01)</option>
                                    </select>
                                    <div class="form-text">Only used if Manual Selection is enabled.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Create/Edit Warehouse Sidebar Card -->
                <div class="col-lg-5 col-md-12">
                    <div class="card border-0 shadow-sm h-100 bg-light">
                        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                            <h5 class="fw-bold text-dark" id="create-wh-title"><i class="fas fa-plus-circle me-2 text-success"></i>Create Warehouse via API</h5>
                            <div class="text-muted small mt-1">Register a new warehouse pickup location on your ParcelX account.</div>
                            <hr class="mt-3 mb-0">
                        </div>
                        <div class="card-body p-4">
                            <!-- Hidden input to track if editing/replacing a warehouse -->
                            <input type="hidden" form="create-wh-form" name="delete_on_success_id" id="edit-warehouse-delete-id" value="">
                            
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Warehouse Title (Nickname)</label>
                                <input type="text" form="create-wh-form" name="address_title" class="form-control" placeholder="e.g. Primary Warehouse" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Sender / Contact Name</label>
                                <input type="text" form="create-wh-form" name="sender_name" class="form-control" placeholder="e.g. Rajesh Kumar" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Full Address</label>
                                <input type="text" form="create-wh-form" name="full_address" class="form-control" placeholder="e.g. Sector 62, Eco Tower" required>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Contact Phone</label>
                                    <input type="text" form="create-wh-form" name="phone" class="form-control" placeholder="9876543210" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Pincode</label>
                                    <input type="text" form="create-wh-form" name="pincode" class="form-control" placeholder="201301" required>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" form="create-wh-form" class="btn btn-success flex-grow-1 py-2" id="create-wh-btn-text"><i class="fas fa-plus me-2"></i>Register Warehouse</button>
                                <button type="button" class="btn btn-outline-secondary py-2" id="cancel-edit-btn" style="display: none;">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Warehouse Management Table Card -->
                <div class="col-12 mt-2">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                            <h5 class="fw-bold text-dark"><i class="fas fa-warehouse me-2 text-primary"></i>Warehouse List & Management</h5>
                            <div class="text-muted small mt-1">Manage active warehouses registered on your ParcelX account. Active pickup location is highlighted in blue.</div>
                            <hr class="mt-3 mb-0">
                        </div>
                        <div class="card-body p-4">
                            @if(!empty($parcelxWarehouses))
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle text-nowrap">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Title / Nickname</th>
                                                <th>Contact Name</th>
                                                <th>Phone</th>
                                                <th>Address</th>
                                                <th>Pincode</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($parcelxWarehouses as $wh)
                                                @php
                                                    $isActive = (string)setting('parcelx_pickup_location') === (string)$wh['warehouse_id'];
                                                @endphp
                                                <tr class="{{ $isActive ? 'table-primary fw-bold text-dark' : '' }}">
                                                    <td><code>{{ $wh['warehouse_id'] }}</code></td>
                                                    <td>
                                                        {{ $wh['title'] }}
                                                        @if($isActive)
                                                            <span class="badge bg-primary ms-2"><i class="fas fa-check-circle me-1"></i>Active Pickup</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $wh['addressee'] }}</td>
                                                    <td>{{ $wh['phone'] }}</td>
                                                    <td>{{ $wh['full_address'] }}, {{ $wh['ciity'] }}, {{ $wh['state'] }}</td>
                                                    <td><code>{{ $wh['pincode'] }}</code></td>
                                                    <td class="text-end">
                                                        <div class="btn-group btn-group-sm">
                                                            <!-- Edit button -->
                                                            <button type="button" 
                                                                    class="btn btn-outline-secondary edit-wh-btn"
                                                                    data-id="{{ $wh['warehouse_id'] }}"
                                                                    data-title="{{ $wh['title'] }}"
                                                                    data-addressee="{{ $wh['addressee'] }}"
                                                                    data-address="{{ $wh['full_address'] }}"
                                                                    data-phone="{{ $wh['phone'] }}"
                                                                    data-pincode="{{ $wh['pincode'] }}">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </button>
                                                            <!-- Delete button -->
                                                            <button type="button" 
                                                                    class="btn btn-outline-danger delete-wh-btn"
                                                                    data-id="{{ $wh['warehouse_id'] }}">
                                                                <i class="fas fa-trash-alt"></i> Delete
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-info-circle fa-2x mb-2 text-warning"></i>
                                    <p class="mb-0">No registered warehouses found on your ParcelX account. Use the registration form above to create one.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Save Button -->
        <div class="col-12 text-end mt-4">
            <button type="submit" class="btn btn-primary btn-lg px-5 shadow-sm"><i class="fas fa-save me-2"></i>Save Configuration</button>
        </div>
    </div>
</form>

<!-- Separate Form for Creating Warehouse -->
<form action="{{ route('admin.shipping-settings.create-warehouse') }}" method="POST" id="create-wh-form" style="display: none;">
    @csrf
</form>

<!-- Separate Form for Deleting Warehouse -->
<form action="" method="POST" id="delete-wh-form" style="display: none;">
    @csrf
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const providerSelect = document.getElementById('shipping_provider');
        
        function toggleProviderCards() {
            const selected = providerSelect.value;
            document.querySelectorAll('.shipping-provider-card').forEach(card => {
                card.style.display = 'none';
            });
            const activeCard = document.getElementById('provider-card-' + selected);
            if (activeCard) {
                activeCard.style.display = 'block';
            }
        }

        providerSelect.addEventListener('change', toggleProviderCards);
        toggleProviderCards(); // Initial load

        // Edit Warehouse Handler
        document.querySelectorAll('.edit-wh-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const title = this.getAttribute('data-title');
                const addressee = this.getAttribute('data-addressee');
                const address = this.getAttribute('data-address');
                const phone = this.getAttribute('data-phone');
                const pincode = this.getAttribute('data-pincode');

                // Populate form inputs
                document.querySelector('input[name="address_title"]').value = title;
                document.querySelector('input[name="sender_name"]').value = addressee;
                document.querySelector('input[name="full_address"]').value = address;
                document.querySelector('input[name="phone"]').value = phone;
                document.querySelector('input[name="pincode"]').value = pincode;

                // Set replacement ID
                document.getElementById('edit-warehouse-delete-id').value = id;

                // Update UI state to Edit
                document.getElementById('create-wh-title').innerHTML = '<i class="fas fa-edit me-2 text-warning"></i>Edit Warehouse #' + id;
                document.getElementById('create-wh-btn-text').innerHTML = '<i class="fas fa-save me-2"></i>Update Warehouse';
                document.getElementById('cancel-edit-btn').style.display = 'inline-block';
                
                // Scroll up smoothly to the warehouse form
                document.getElementById('provider-card-parcelx').scrollIntoView({ behavior: 'smooth' });
            });
        });

        // Cancel Edit Handler
        document.getElementById('cancel-edit-btn').addEventListener('click', function() {
            // Reset form fields
            document.querySelector('input[name="address_title"]').value = '';
            document.querySelector('input[name="sender_name"]').value = '';
            document.querySelector('input[name="full_address"]').value = '';
            document.querySelector('input[name="phone"]').value = '';
            document.querySelector('input[name="pincode"]').value = '';
            document.getElementById('edit-warehouse-delete-id').value = '';

            // Reset UI state
            document.getElementById('create-wh-title').innerHTML = '<i class="fas fa-plus-circle me-2 text-success"></i>Create Warehouse via API';
            document.getElementById('create-wh-btn-text').innerHTML = '<i class="fas fa-plus me-2"></i>Register Warehouse';
            this.style.display = 'none';
        });

        // Delete Warehouse Handler
        document.querySelectorAll('.delete-wh-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                if (confirm('Are you sure you want to delete warehouse #' + id + '? This action is permanent.')) {
                    const deleteForm = document.getElementById('delete-wh-form');
                    deleteForm.action = '/admin/shipping-settings/delete-warehouse/' + id;
                    deleteForm.submit();
                }
            });
        });

        // Toggle Credentials Readonly Lock
        const lockBtn = document.getElementById('edit-credentials-lock-btn');
        let isLocked = true;

        if (lockBtn) {
            lockBtn.addEventListener('click', function() {
                isLocked = !isLocked;
                const fields = [
                    'parcelx_access_key',
                    'parcelx_secret_key',
                    'parcelx_access_token',
                    'parcelx_base_url'
                ];

                fields.forEach(id => {
                    const el = document.getElementById(id);
                    if (el) {
                        if (isLocked) {
                            el.setAttribute('readonly', 'readonly');
                        } else {
                            el.removeAttribute('readonly');
                        }
                    }
                });

                if (isLocked) {
                    this.innerHTML = '<i class="fas fa-lock me-1"></i> Edit Keys';
                    this.classList.remove('btn-primary');
                    this.classList.add('btn-outline-primary');
                } else {
                    this.innerHTML = '<i class="fas fa-lock-open me-1"></i> Lock Keys';
                    this.classList.remove('btn-outline-primary');
                    this.classList.add('btn-primary');
                }
            });
        }
    });
</script>
@endsection
