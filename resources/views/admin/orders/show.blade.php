@extends('admin.layouts.app')

@section('title', 'Order Details')
@section('page-title', 'Order Details')

@section('content')
<div class="card">
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
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
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
                    <td>{{ $item->quantity }}</td>
                    <td>₹{{ number_format($item->price, 2) }}</td>
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
                            <th>Discount</th>
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
@endsection