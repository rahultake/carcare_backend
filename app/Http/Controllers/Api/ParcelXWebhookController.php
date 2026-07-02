<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ParcelXWebhookController extends Controller
{
    public function handle(Request $request)
    {
        Log::info("Incoming ParcelX Webhook Payload: " . json_encode($request->all()));

        // Resolve payload structure (support both nested current_status and flat structures)
        $awb = $request->input('current_status.awb') ?: $request->input('awb') ?: $request->input('awb_number');
        $statusCode = (string)($request->input('current_status.status_code') ?: $request->input('status_code') ?: $request->input('statusCode'));
        $statusTitle = $request->input('current_status.status_title') ?: $request->input('status_title') ?: $request->input('status');

        if (!$awb) {
            Log::warning("ParcelX Webhook ignored: AWB number missing in payload.");
            return response()->json(['status' => 'error', 'message' => 'AWB missing'], 400);
        }

        // Find matching order by AWB code
        $order = Order::where('awb_code', $awb)->first();

        if (!$order) {
            Log::warning("ParcelX Webhook: No order found for AWB: " . $awb);
            return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);
        }

        // Update shipping provider history if needed
        $shipmentData = json_decode($order->shipment_data, true) ?: [];
        $shipmentData['latest_webhook_status'] = [
            'status_code' => $statusCode,
            'status_title' => $statusTitle,
            'event_date'   => $request->input('current_status.event_date') ?: $request->input('event_date') ?: now()->toDateTimeString(),
            'updated_at'   => now()->toDateTimeString()
        ];
        
        $orderUpdate = [
            'shipment_data' => json_encode($shipmentData)
        ];

        // Map status codes to our local order and shipping statuses
        switch ($statusCode) {
            case '220': // Pending
            case '221': // Booked
            case '222': // Manifested
            case '232': // Pickup Pending
            case '238': // Out For Pickup
                $orderUpdate['shipping_status'] = 'ready_to_ship';
                break;

            case '223': // In Transit
            case '230': // Picked Up
                $orderUpdate['shipping_status'] = 'shipped';
                if (empty($order->shipped_at)) {
                    $orderUpdate['shipped_at'] = now();
                }
                break;

            case '226': // Delivered To Consignee
                $orderUpdate['status'] = 'delivered';
                $orderUpdate['shipping_status'] = 'delivered';
                if (empty($order->delivered_at)) {
                    $orderUpdate['delivered_at'] = now();
                }
                break;

            case '227': // Cancelled
                $orderUpdate['status'] = 'cancelled';
                $orderUpdate['shipping_status'] = 'cancelled';
                break;

            case '224': // RTS
            case '225': // RTO
            case '234': // RTO In Transit
            case '235': // RTO Pending
            case '236': // RTO - OFD
                $orderUpdate['shipping_status'] = 'rto';
                break;
        }

        $order->update($orderUpdate);

        Log::info("ParcelX Webhook processed successfully for Order #{$order->order_number}. Local statuses set: Status={$order->status}, Shipping Status={$order->shipping_status}");

        return response()->json(['status' => 'success', 'message' => 'Status updated']);
    }
}
