<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShiprocketWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        Log::info('Shiprocket Webhook Received: ' . json_encode($request->all()));

        // 1. Security Check: Verify webhook token if configured in .env
        $configuredToken = env('SHIPROCKET_WEBHOOK_TOKEN');
        if ($configuredToken) {
            $incomingToken = $request->header('X-Shiprocket-Token') ?: $request->input('webhook_token');
            if ($incomingToken !== $configuredToken) {
                Log::warning('Unauthorized Shiprocket Webhook Attempt: Invalid Token.');
                return response()->json(['message' => 'Unauthorized'], 401);
            }
        }

        // 2. Identify the order
        $orderId = $request->input('order_id');
        $shipmentId = $request->input('shipment_id');
        $awbCode = $request->input('awb');

        $order = null;

        if ($orderId) {
            $order = Order::where('order_number', $orderId)->first();
        }

        if (!$order && $shipmentId) {
            $order = Order::where('shiprocket_shipment_id', $shipmentId)->first();
        }

        if (!$order && $awbCode) {
            $order = Order::where('tracking_number', $awbCode)
                          ->orWhere('awb_code', $awbCode)
                          ->first();
        }

        if (!$order) {
            Log::warning('Shiprocket Webhook: Order not found for payload.', $request->all());
            return response()->json(['message' => 'Order not found'], 404);
        }

        // 3. Extract and normalize Shiprocket tracking status
        // Shiprocket typical statuses: 'PICKED UP', 'IN TRANSIT', 'DELIVERED', 'CANCELLED', 'RTO INITIATED', 'RTO DELIVERED'
        $shiprocketStatus = strtoupper(trim($request->input('current_status')));

        if (empty($shiprocketStatus)) {
            // Check status_name or status as fallbacks
            $shiprocketStatus = strtoupper(trim($request->input('status', $request->input('status_name', ''))));
        }

        $data = [];

        // Save tracking details if passed
        if ($awbCode) {
            $data['tracking_number'] = $awbCode;
            $data['awb_code'] = $awbCode;
        }
        if ($request->filled('courier_name')) {
            $data['shipping_provider'] = $request->input('courier_name');
        }

        switch ($shiprocketStatus) {
            case 'PICKED UP':
            case 'DISPATCHED':
            case 'IN TRANSIT':
            case 'OUT FOR DELIVERY':
                $data['shipping_status'] = 'shipped';
                if (!$order->shipped_at) {
                    $data['shipped_at'] = now();
                }
                break;

            case 'DELIVERED':
                $data['status'] = 'completed';
                $data['shipping_status'] = 'delivered';
                if (!$order->delivered_at) {
                    $data['delivered_at'] = now();
                }
                break;

            case 'CANCELLED':
                $data['status'] = 'cancelled';
                $data['shipping_status'] = 'cancelled';
                break;

            case 'RTO INITIATED':
            case 'RTO DELIVERED':
            case 'RTO RECEIVED':
                $data['status'] = 'refunded';
                $data['shipping_status'] = 'cancelled'; // or custom RTO shipping status
                break;
        }

        if (!empty($data)) {
            $order->update($data);
            Log::info("Shiprocket Webhook: Order #{$order->order_number} updated successfully.", $data);
        }

        return response()->json(['status' => 'success', 'message' => 'Webhook processed successfully']);
    }
}
