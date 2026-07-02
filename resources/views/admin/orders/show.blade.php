@extends('admin.layouts.app')

@section('title', 'Order Details')
@section('page-title', 'Order Details')

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@php
    $shipmentData = json_decode($order->shipment_data, true) ?: [];
    $provider = $shipmentData['provider'] ?? setting('shipping_provider', 'shiprocket');
    
    // Check if there is an explicit balance/wallet error message
    $responsemsg = $shipmentData['responsemsg'] ?? '';
    $responseStr = is_array($responsemsg) ? implode(' ', $responsemsg) : (string)$responsemsg;
    $hasBalanceError = (strpos(strtolower($responseStr), 'balance') !== false || strpos(strtolower($responseStr), 'insuff') !== false);
    
    // If order is paid but has no AWB code, it is unbooked
    $isUnbookedPaidOrder = ($order->payment_status === 'completed' || $order->status === 'processing') && empty($order->awb_code);
@endphp

@if($isUnbookedPaidOrder)
    @if($provider === 'parcelx')
        @if($hasBalanceError)
            <div class="alert alert-danger border-danger mb-4 shadow-sm" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-wallet fa-2x me-3 text-danger"></i>
                    <div>
                        <h5 class="alert-heading fw-bold mb-1"><i class="fas fa-exclamation-circle"></i> ParcelX Booking Failed: Insufficient Wallet Balance</h5>
                        <p class="mb-1">This order's payment is successful, but booking with **ParcelX** failed because your merchant account has insufficient wallet balance. ParcelX requires prepaid wallet credits to book shipments, generate AWB tracking codes, and print shipping labels.</p>
                        <p class="mb-2 small text-muted"><strong>Carrier Response Error:</strong> <code>{{ $responseStr }}</code></p>
                        <div class="d-flex gap-2">
                            <a href="https://app.parcelx.in/" target="_blank" class="btn btn-danger btn-sm fw-bold">
                                <i class="fas fa-external-link-alt me-1"></i> Recharge ParcelX Wallet
                            </a>
                            <form action="{{ route('admin.orders.sync-carrier', $order->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger btn-sm fw-bold">
                                    <i class="fas fa-sync-alt me-1"></i> Retry Booking
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-warning border-warning mb-4 shadow-sm" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle fa-2x me-3 text-warning"></i>
                    <div>
                        <h5 class="alert-heading fw-bold mb-1">ParcelX Shipment Booking Required</h5>
                        <p class="mb-1">Payment is verified. To generate the AWB tracking code and print shipping labels, you need to book this order with ParcelX. Ensure your **ParcelX Wallet has a positive balance** before booking.</p>
                        <div class="d-flex gap-2 mt-2">
                            <form action="{{ route('admin.orders.sync-carrier', $order->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-warning btn-sm fw-bold">
                                    <i class="fas fa-shipping-fast me-1"></i> Book Shipment Now
                                </button>
                            </form>
                            <a href="https://app.parcelx.in/" target="_blank" class="btn btn-outline-dark btn-sm fw-bold">
                                <i class="fas fa-external-link-alt me-1"></i> Open Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @elseif($provider === 'shiprocket')
        <div class="alert alert-warning border-warning mb-4 shadow-sm" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-info-circle fa-2x me-3 text-warning"></i>
                <div>
                    <h5 class="alert-heading fw-bold mb-1">Shiprocket Shipment Booking Required</h5>
                    <p class="mb-1">Payment is verified. To generate the AWB tracking code and print shipping labels, you need to book this order with Shiprocket.</p>
                    <div class="d-flex gap-2 mt-2">
                        <form action="{{ route('admin.orders.sync-carrier', $order->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm fw-bold">
                                <i class="fas fa-shipping-fast me-1"></i> Book Shipment Now
                            </button>
                        </form>
                        <a href="https://shiprocket.co/" target="_blank" class="btn btn-outline-dark btn-sm fw-bold">
                            <i class="fas fa-external-link-alt me-1"></i> Open Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endif

<div class="row g-4">
    <div class="col-lg-8 col-md-12">
        <div class="card mb-4">
            <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5>Order #{{ $order->order_number }}</h5>
            <span class="badge bg-secondary">{{ ucfirst($order->status) }}</span>
        </div>
        <hr>

        <div class="row">
            <div class="col-md-6">
                <p><strong>Status:</strong> {{ ucfirst($order->status) }}</p>
                <p><strong>Payment Status:</strong> {{ ucfirst($order->payment_status) }}</p>
                <p><strong>Payment Method:</strong> {{ strtoupper($order->payment_method ?? '-') }}</p>
                @if($order->razorpay_payment_id)
                    <p><strong>Razorpay Payment ID:</strong> {{ $order->razorpay_payment_id }}</p>
                @endif
                @if($order->payment)
                    <div class="mt-3 pt-3 border-top">
                        <h6 class="text-primary mb-2"><i class="fas fa-credit-card me-1"></i> Razorpay Payment Info</h6>
                        <p class="mb-1 small"><strong>Razorpay Order ID:</strong> <code>{{ $order->payment->razorpay_order_id ?? '-' }}</code></p>
                        <p class="mb-1 small"><strong>Razorpay Payment ID:</strong> <code>{{ $order->payment->razorpay_payment_id ?? '-' }}</code></p>
                        <p class="mb-1 small"><strong>Amount Paid:</strong> {{ strtoupper($order->payment->currency ?? 'INR') }} {{ number_format($order->payment->amount, 2) }}</p>
                        <p class="mb-1 small"><strong>Transaction Status:</strong> 
                            @if($order->payment->status === 'captured')
                                <span class="badge bg-success">Captured</span>
                            @elseif($order->payment->status === 'refunded')
                                <span class="badge bg-info text-dark">Refunded</span>
                            @elseif($order->payment->status === 'failed')
                                <span class="badge bg-danger">Failed</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($order->payment->status) }}</span>
                            @endif
                        </p>
                        @if($order->payment->method)
                            <p class="mb-1 small"><strong>Method:</strong> <span class="badge bg-light text-dark border">{{ strtoupper($order->payment->method) }}</span></p>
                        @endif
                        @if($order->payment->error_description)
                            <p class="mb-1 text-danger small"><strong>Error Detail:</strong> {{ $order->payment->error_description }}</p>
                        @endif
                    </div>
                @endif
                @if($order->coupon_code)
                    <p><strong>Coupon Applied:</strong> <span class="badge bg-success">{{ $order->coupon_code }}</span></p>
                @endif
            </div>
            <div class="col-md-6">
                @php
                $shipping = json_decode($order->shipping_address, true);
                @endphp
                <p>
                    <strong>Shipping Details:</strong><br>
                    {{ $shipping['name'] }}<br>
                    {{ $shipping['address_line_1'] ?? '-' }}<br>
                    {{ $shipping['address_line_2'] ?? '-' }}<br>
                    {{ $shipping['city'] }}, {{ $shipping['state'] }} - {{ $shipping['postal_code'] }}<br>
                    {{ $shipping['country'] }}<br>
                    <strong>Company Name:</strong> {{ $shipping['company_name'] ?? '-' }}<br>
                    <strong>GSTIN Number:</strong> {{ $shipping['gstin_number'] ?? '-' }}<br>
                </p>
            </div>
        </div>

        <h6 class="mt-4">Items Summary</h6>
        <div class="table-responsive">
            <table class="table table-striped align-middle text-nowrap">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>HSN Code</th>
                        <th>Qty</th>
                        <th>Cost Price</th>
                        <th>Compare Price</th>
                        <th>Main Price (Paid)</th>
                        <th>Discount</th>
                        <th>GST Breakdown</th>
                        <th>GST Amount</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->product_name }}</td>
                        <td>{{ $item->product_sku }}</td>
                        <td>{{ $item->product?->categories->first(fn($cat) => !empty($cat->hsn_code))?->hsn_code ?: '-' }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ $item->product?->cost_price ? '₹' . number_format($item->product->cost_price, 2) : '-' }}</td>
                        <td>{{ $item->product?->compare_price ? '₹' . number_format($item->product->compare_price, 2) : '-' }}</td>
                        <td>₹{{ number_format($item->price, 2) }}</td>
                        <td>{{ $item->product?->discount_percentage ? number_format($item->product->discount_percentage, 1) . '%' : '-' }}</td>
                        <td>
                            @if($item->igst_percent > 0)
                                IGST ({{ number_format($item->igst_percent, 1) }}%)
                            @elseif($item->cgst_percent > 0 || $item->sgst_percent > 0)
                                CGST ({{ number_format($item->cgst_percent, 1) }}%) + SGST ({{ number_format($item->sgst_percent, 1) }}%)
                            @else
                                Exempt (0%)
                            @endif
                        </td>
                        <td>₹{{ number_format($item->tax_amount, 2) }}</td>
                        <td class="text-end">₹{{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                @if($order->notes)
                    <div class="alert alert-info">
                        <strong>Notes:</strong> {{ $order->notes }}
                    </div>
                @endif
            </div>
            <div class="col-md-6">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tr>
                            <th>Subtotal (Gross)</th>
                            <td class="text-end">₹{{ number_format($order->subtotal, 2) }}</td>
                        </tr>
                        @if($order->discount > 0)
                        <tr>
                            <th>
                                Discount
                                @if($order->coupon_code)
                                    <small class="badge bg-success ms-1">Coupon: {{ $order->coupon_code }}</small>
                                @endif
                            </th>
                            <td class="text-end text-danger">-₹{{ number_format($order->discount, 2) }}</td>
                        </tr>
                        @endif
                        @if($order->shipping_cost > 0)
                        <tr>
                            <th>Shipping Cost</th>
                            <td class="text-end">₹{{ number_format($order->shipping_cost, 2) }}</td>
                        </tr>
                        @endif
                        @if($order->cgst_amount > 0)
                        <tr>
                            <th>CGST</th>
                            <td class="text-end">₹{{ number_format($order->cgst_amount, 2) }}</td>
                        </tr>
                        @endif
                        @if($order->sgst_amount > 0)
                        <tr>
                            <th>SGST</th>
                            <td class="text-end">₹{{ number_format($order->sgst_amount, 2) }}</td>
                        </tr>
                        @endif
                        @if($order->igst_amount > 0)
                        <tr>
                            <th>IGST</th>
                            <td class="text-end">₹{{ number_format($order->igst_amount, 2) }}</td>
                        </tr>
                        @endif
                        <tr class="table-light">
                            <th>Total GST Included</th>
                            <td class="text-end">₹{{ number_format($order->tax, 2) }}</td>
                        </tr>
                        <tr class="table-primary">
                            <th><strong>Final Paid Amount</strong></th>
                            <th class="text-end"><strong>₹{{ number_format($order->total_amount, 2) }}</strong></th>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        </div>
        </div>

        @php
            $shipmentData = json_decode($order->shipment_data, true) ?: [];
            $provider = $shipmentData['provider'] ?? ($order->awb_code ? 'shiprocket' : null);
        @endphp

        @if($provider === 'parcelx')
        <!-- ParcelX Tracking & Shipment Details Card -->
        <div class="card mb-4 border-warning shadow-sm">
            <div class="card-header bg-warning text-dark py-3">
                <h6 class="mb-0 font-weight-bold"><i class="fas fa-route me-1"></i> ParcelX Tracking & Shipment Details</h6>
            </div>
            <div class="card-body">
                <div class="row mb-3 align-items-center">
                    <div class="col-md-3 mb-2 mb-md-0">
                        <span class="text-muted small d-block">ORDER ID</span>
                        <strong>{{ $shipmentData['data']['order_id'] ?? 'N/A' }}</strong>
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <span class="text-muted small d-block">AWB NUMBER</span>
                        <strong>{{ $order->awb_code ?? 'N/A' }}</strong>
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <span class="text-muted small d-block">COURIER ASSIGNED</span>
                        <strong>{{ strtoupper($order->courier_company_id ?? 'N/A') }}</strong>
                    </div>
                    <div class="col-md-3 text-md-end">
                        <form action="{{ route('admin.orders.sync-carrier', $order->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-warning btn-sm fw-bold">
                                <i class="fas fa-sync-alt me-1"></i> Sync Scans Log
                            </button>
                        </form>
                    </div>
                </div>

                @if(!empty($shipmentData['latest_carrier_track']))
                    <div class="alert alert-light border mb-4">
                        <h6 class="fw-bold mb-1 text-warning"><i class="fas fa-info-circle text-warning me-1"></i> Current Status: {{ strtoupper($shipmentData['latest_carrier_track']['status_title'] ?? 'Unknown') }}</h6>
                        <p class="mb-0 text-muted small">{{ $shipmentData['latest_carrier_track']['status_description'] ?? 'No status description available.' }}</p>
                        @if(!empty($shipmentData['latest_carrier_track']['event_date']))
                            <small class="text-muted d-block mt-1">Last scanned on: {{ $shipmentData['latest_carrier_track']['event_date'] }}</small>
                        @endif
                    </div>
                @endif

                <h6 class="fw-bold mb-3"><i class="fas fa-list-ul me-1"></i> Scans History & Milestones</h6>
                @if(!empty($shipmentData['scans']))
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th>Event Date & Time</th>
                                    <th>Location</th>
                                    <th>Activity / Status</th>
                                    <th>Details / Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(array_reverse($shipmentData['scans']) as $scan)
                                    <tr>
                                        <td><code>{{ $scan['event_date'] ?? 'N/A' }}</code></td>
                                        <td>{{ $scan['status_location'] ?: 'In Transit / Hub' }}</td>
                                        <td>
                                            <span class="badge bg-secondary text-white">{{ strtoupper($scan['status_title'] ?? 'N/A') }}</span>
                                        </td>
                                        <td>{{ $scan['status_description'] ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4 text-muted bg-light rounded border">
                        <i class="fas fa-route fa-2x mb-2 text-warning"></i>
                        <p class="mb-0">No scans logged yet. Click <strong>Sync Scans Log</strong> above to fetch recent milestones.</p>
                    </div>
                @endif
            </div>
        </div>
        @elseif($provider === 'shiprocket')
        <!-- Shiprocket Tracking & Shipment Details Card -->
        <div class="card mb-4 border-success shadow-sm">
            <div class="card-header bg-success text-white py-3">
                <h6 class="mb-0 font-weight-bold"><i class="fas fa-route me-1"></i> Shiprocket Tracking & Shipment Details</h6>
            </div>
            <div class="card-body">
                <div class="row mb-3 align-items-center">
                    <div class="col-md-3 mb-2 mb-md-0">
                        <span class="text-muted small d-block">ORDER ID</span>
                        <strong>{{ $order->shiprocket_order_id ?? 'N/A' }}</strong>
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <span class="text-muted small d-block">AWB NUMBER</span>
                        <strong>{{ $order->awb_code ?? 'N/A' }}</strong>
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <span class="text-muted small d-block">COURIER ASSIGNED</span>
                        <strong>{{ strtoupper($order->courier_company_id ?? 'N/A') }}</strong>
                    </div>
                    <div class="col-md-3 text-md-end">
                        <form action="{{ route('admin.orders.sync-carrier', $order->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm fw-bold">
                                <i class="fas fa-sync-alt me-1"></i> Sync Scans Log
                            </button>
                        </form>
                    </div>
                </div>

                @if(!empty($shipmentData['latest_carrier_track']))
                    <div class="alert alert-light border mb-4">
                        <h6 class="fw-bold mb-1 text-success"><i class="fas fa-info-circle text-success me-1"></i> Current Status: {{ strtoupper($shipmentData['latest_carrier_track']['current_status'] ?? 'Unknown') }}</h6>
                        @if(!empty($shipmentData['latest_carrier_track']['pickup_date']))
                            <small class="text-muted d-block mt-1">Pickup Date: {{ $shipmentData['latest_carrier_track']['pickup_date'] }}</small>
                        @endif
                        @if(!empty($shipmentData['latest_carrier_track']['delivered_date']))
                            <small class="text-muted d-block mt-1">Delivered Date: {{ $shipmentData['latest_carrier_track']['delivered_date'] }}</small>
                        @endif
                    </div>
                @endif

                <h6 class="fw-bold mb-3"><i class="fas fa-list-ul me-1"></i> Scans History & Milestones</h6>
                @if(!empty($shipmentData['scans']))
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th>Event Date & Time</th>
                                    <th>Location</th>
                                    <th>Activity / Status</th>
                                    <th>Details / Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(array_reverse($shipmentData['scans']) as $scan)
                                    <tr>
                                        <td><code>{{ $scan['date'] ?? 'N/A' }}</code></td>
                                        <td>{{ $scan['location'] ?: 'In Transit / Hub' }}</td>
                                        <td>
                                            <span class="badge bg-secondary text-white">{{ strtoupper($scan['status'] ?? 'N/A') }}</span>
                                        </td>
                                        <td>{{ $scan['activity'] ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4 text-muted bg-light rounded border">
                        <i class="fas fa-route fa-2x mb-2 text-success"></i>
                        <p class="mb-0">No scans logged yet. Click <strong>Sync Scans Log</strong> above to fetch recent milestones.</p>
                    </div>
                @endif
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4 col-md-12">
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 font-weight-bold text-primary">Update Order Status</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.orders.update-status', $order->id) }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="status" class="form-label font-weight-bold">Order Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="pending_payment" {{ $order->status === 'pending_payment' ? 'selected' : '' }}>Pending Payment</option>
                            <option value="processing" {{ $order->status === 'processing' ? 'selected' : '' }}>Processing (Paid)</option>
                            <option value="completed" {{ $order->status === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            <option value="refunded" {{ $order->status === 'refunded' ? 'selected' : '' }}>Refunded</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="shipping_status" class="form-label font-weight-bold">Shipping Status</label>
                        <select name="shipping_status" id="shipping_status" class="form-select">
                            <option value="pending" {{ $order->shipping_status === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="processing" {{ $order->shipping_status === 'processing' ? 'selected' : '' }}>Processing</option>
                            <option value="shipped" {{ $order->shipping_status === 'shipped' ? 'selected' : '' }}>Shipped</option>
                            <option value="delivered" {{ $order->shipping_status === 'delivered' ? 'selected' : '' }}>Delivered</option>
                            <option value="cancelled" {{ $order->shipping_status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="tracking_number" class="form-label font-weight-bold">Tracking Number (AWB)</label>
                        <input type="text" name="tracking_number" id="tracking_number" class="form-control" value="{{ $order->tracking_number }}" placeholder="e.g. 14325678">
                    </div>

                    <div class="mb-3">
                        <label for="shipping_provider" class="form-label font-weight-bold">Shipping Provider (Courier)</label>
                        <input type="text" name="shipping_provider" id="shipping_provider" class="form-control" value="{{ $order->shipping_provider }}" placeholder="e.g. Delhivery, BlueDart, Shiprocket">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Update Status</button>
                </form>
            </div>
        </div>

        @if($order->shiprocket_order_id)
        @php
            $shipmentData = json_decode($order->shipment_data, true) ?: [];
            $provider = $shipmentData['provider'] ?? 'shiprocket';
            $isParcelX = $provider === 'parcelx';
            $cardBorder = $isParcelX ? 'border-warning' : 'border-success';
            $cardHeaderBg = $isParcelX ? 'bg-warning text-dark' : 'bg-success text-white';
            $providerLabel = $isParcelX ? 'ParcelX' : 'Shiprocket';
            $dashboardUrl = $isParcelX ? 'https://app.parcelx.in/' : 'https://shiprocket.co/';
            
            $trackingUrl = null;
            if ($order->awb_code) {
                $trackingUrl = $isParcelX 
                    ? "https://app.parcelx.in/track?awb={$order->awb_code}" 
                    : "https://shiprocket.co/tracking/{$order->awb_code}";
            }
        @endphp
        <div class="card mb-4 {{ $cardBorder }} shadow-sm">
            <div class="card-header {{ $cardHeaderBg }} py-3">
                <h6 class="mb-0 font-weight-bold"><i class="fas fa-shipping-fast me-1"></i> {{ $providerLabel }} Shipment</h6>
            </div>
            <div class="card-body">
                <p class="mb-2"><strong>Carrier Order ID:</strong> {{ $order->shiprocket_order_id }}</p>
                <p class="mb-2"><strong>Pickup / Shipment ID:</strong> {{ $order->shiprocket_shipment_id }}</p>
                @if($order->awb_code)
                    <p class="mb-2"><strong>AWB / Tracking Number:</strong> <code>{{ $order->awb_code }}</code></p>
                @endif
                @if($order->courier_company_id)
                    <p class="mb-2"><strong>Courier Assigned:</strong> {{ $order->courier_company_id }}</p>
                @endif
                
                @if($trackingUrl)
                    <a href="{{ $trackingUrl }}" target="_blank" class="btn btn-primary btn-sm w-100 mt-2">
                        <i class="fas fa-search-location me-1"></i> Track on {{ $providerLabel }}
                    </a>
                @endif
                
                <form action="{{ route('admin.orders.sync-carrier', $order->id) }}" method="POST" class="mt-2">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="fas fa-sync-alt me-1"></i> Sync Carrier Status
                    </button>
                </form>

                <a href="{{ $dashboardUrl }}" target="_blank" class="btn btn-link btn-sm w-100 mt-2 text-decoration-none text-muted">
                    Open {{ $providerLabel }} Dashboard
                </a>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection