<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('user')
            ->latest()->paginate(20);

        return view('admin.orders.index', compact('orders'));
    }

    public function show($id)
    {
        $order = Order::with(['items.product.categories','user','payment'])->findOrFail($id);
        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(\Illuminate\Http\Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|max:255',
            'shipping_status' => 'required|string|max:255',
            'tracking_number' => 'nullable|string|max:255',
            'shipping_provider' => 'nullable|string|max:255',
        ]);

        $order = Order::findOrFail($id);

        $data = [
            'status' => $request->status,
            'shipping_status' => $request->shipping_status,
            'tracking_number' => $request->tracking_number,
            'shipping_provider' => $request->shipping_provider,
        ];

        // Auto-assign timestamps on status transitions
        if ($request->shipping_status === 'shipped' && !$order->shipped_at) {
            $data['shipped_at'] = now();
        }

        if ($request->shipping_status === 'delivered' && !$order->delivered_at) {
            $data['delivered_at'] = now();
        }

        if ($request->status === 'completed' && !$order->delivered_at) {
            $data['delivered_at'] = now();
        }

        $order->update($data);

        return redirect()->back()->with('success', 'Order status updated successfully.');
    }

    public function syncCarrierStatus($id)
    {
        $order = Order::findOrFail($id);
        $shipmentData = json_decode($order->shipment_data, true) ?: [];
        $provider = $shipmentData['provider'] ?? 'shiprocket';
        $awb = $order->awb_code ?: $order->tracking_number;

        if (!$awb) {
            // Trigger booking if order is paid/processing but not booked yet
            $provider = setting('shipping_provider', 'shiprocket');
            if ($provider === 'parcelx') {
                try {
                    $parcelx = new \App\Services\ParcelXService();
                    $shipResponse = $parcelx->createOrder($order);

                    if ($shipResponse && isset($shipResponse['status']) && $shipResponse['status']) {
                        $pxData = $shipResponse['data'] ?? [];
                        $order->update([
                            'shiprocket_order_id'      => $pxData['order_number'] ?? null,
                            'shiprocket_shipment_id'   => $pxData['pickup_id'] ?? null,
                            'awb_code'                 => $pxData['awb_number'] ?? null,
                            'courier_company_id'       => $pxData['courier_code'] ?? null,
                            'shipment_data'            => json_encode(array_merge($shipResponse, ['provider' => 'parcelx'])),
                        ]);
                        return back()->with('success', 'Order booked successfully with ParcelX. AWB: ' . ($pxData['awb_number'] ?? ''));
                    } else {
                        $errMsg = isset($shipResponse['responsemsg']) 
                            ? (is_array($shipResponse['responsemsg']) ? implode(', ', $shipResponse['responsemsg']) : $shipResponse['responsemsg']) 
                            : 'Unknown error';
                            
                        // Save failed response to shipment_data so the admin view can display the specific error
                        $order->update([
                            'shipment_data' => json_encode(array_merge($shipResponse ?: [], ['provider' => 'parcelx', 'responsemsg' => $errMsg]))
                        ]);
                        return back()->with('error', 'ParcelX booking failed: ' . $errMsg);
                    }
                } catch (\Exception $e) {
                    return back()->with('error', 'ParcelX booking exception: ' . $e->getMessage());
                }
            } else {
                try {
                    $shiprocket = new \App\Services\ShiprocketService();
                    $shipResponse = $shiprocket->createOrder($order);

                    if ($shipResponse && isset($shipResponse['order_id'])) {
                        $order->update([
                            'shiprocket_order_id'      => $shipResponse['order_id'] ?? null,
                            'shiprocket_shipment_id'   => $shipResponse['shipment_id'] ?? null,
                            'awb_code'                 => $shipResponse['awb_code'] ?? null,
                            'courier_company_id'       => $shipResponse['courier_company_id'] ?? null,
                            'shipment_data'            => json_encode(array_merge($shipResponse, ['provider' => 'shiprocket'])),
                        ]);
                        return back()->with('success', 'Order booked successfully with Shiprocket. AWB: ' . ($shipResponse['awb_code'] ?? ''));
                    } else {
                        $errMsg = $shipResponse['message'] ?? 'Unknown error';
                        $order->update([
                            'shipment_data' => json_encode(array_merge($shipResponse ?: [], ['provider' => 'shiprocket', 'message' => $errMsg]))
                        ]);
                        return back()->with('error', 'Shiprocket booking failed: ' . $errMsg);
                    }
                } catch (\Exception $e) {
                    return back()->with('error', 'Shiprocket booking exception: ' . $e->getMessage());
                }
            }
        }

        if ($provider === 'parcelx') {
            $token = trim(setting('parcelx_access_token'));
            if (!$token) {
                return back()->with('error', 'ParcelX credentials not configured.');
            }

            try {
                $response = Http::withHeaders([
                    'access-token' => $token,
                    'Content-Type' => 'application/json'
                ])->get('https://app.parcelx.in/api/v3/track_order', [
                    'awb' => $awb
                ]);

                if ($response->successful()) {
                    $body = $response->json();
                    if (isset($body['status']) && $body['status'] && isset($body['current_status'])) {
                        $current = $body['current_status'];
                        $statusCode = (string)($current['status_code'] ?? '');
                        
                        $shipmentData['latest_carrier_track'] = $current;
                        $shipmentData['scans'] = $body['scans'] ?? [];
                        $orderUpdate = ['shipment_data' => json_encode($shipmentData)];

                        switch ($statusCode) {
                            case '220':
                            case '221':
                            case '222':
                            case '232':
                            case '238':
                                $orderUpdate['shipping_status'] = 'ready_to_ship';
                                break;
                            case '223':
                            case '230':
                                $orderUpdate['shipping_status'] = 'shipped';
                                if (!$order->shipped_at) {
                                    $orderUpdate['shipped_at'] = now();
                                }
                                break;
                            case '226':
                                $orderUpdate['status'] = 'delivered';
                                $orderUpdate['shipping_status'] = 'delivered';
                                if (!$order->delivered_at) {
                                    $orderUpdate['delivered_at'] = now();
                                }
                                break;
                            case '227':
                                $orderUpdate['status'] = 'cancelled';
                                $orderUpdate['shipping_status'] = 'cancelled';
                                break;
                            case '224':
                            case '225':
                            case '234':
                            case '235':
                            case '236':
                                $orderUpdate['shipping_status'] = 'rto';
                                break;
                        }

                        $order->update($orderUpdate);
                        return back()->with('success', 'Carrier status synchronized successfully: ' . ($current['status_title'] ?? 'Updated'));
                    }
                    return back()->with('error', $body['responsemsg'] ?? 'Tracking data empty.');
                }
                return back()->with('error', 'ParcelX API failed: Status ' . $response->status());
            } catch (\Exception $e) {
                return back()->with('error', 'Sync failed: ' . $e->getMessage());
            }
        } else {
            // Shiprocket Sync
            try {
                $shiprocket = new \App\Services\ShiprocketService();
                $baseUrl = setting('shiprocket_base_url', 'https://apiv2.shiprocket.in/v1/external');
                
                // Get token using reflection
                $tokenProp = new \ReflectionProperty($shiprocket, 'token');
                $tokenProp->setAccessible(true);
                $token = $tokenProp->getValue($shiprocket);

                if (!$token) {
                    return back()->with('error', 'Shiprocket credentials invalid or not configured.');
                }

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json'
                ])->get($baseUrl . '/courier/track/shipment', [
                    'awb_code' => $awb
                ]);

                if ($response->successful()) {
                    $body = $response->json();
                    $trackData = $body[$awb]['tracking_data'] ?? [];
                    if (isset($trackData['shipment_track'][0])) {
                        $track = $trackData['shipment_track'][0];
                        $status = strtolower($track['current_status'] ?? '');
                        
                        $shipmentData['latest_carrier_track'] = $track;
                        $shipmentData['scans'] = $trackData['shipment_track_activities'] ?? [];
                        $orderUpdate = ['shipment_data' => json_encode($shipmentData)];

                        if ($status === 'delivered') {
                            $orderUpdate['status'] = 'delivered';
                            $orderUpdate['shipping_status'] = 'delivered';
                            if (!$order->delivered_at) {
                                $orderUpdate['delivered_at'] = now();
                            }
                        } elseif (in_array($status, ['shipped', 'in transit', 'out for delivery'])) {
                            $orderUpdate['shipping_status'] = 'shipped';
                            if (!$order->shipped_at) {
                                $orderUpdate['shipped_at'] = now();
                            }
                        } elseif ($status === 'cancelled') {
                            $orderUpdate['status'] = 'cancelled';
                            $orderUpdate['shipping_status'] = 'cancelled';
                        } elseif (in_array($status, ['rto', 'rts', 'returned'])) {
                            $orderUpdate['shipping_status'] = 'rto';
                        }

                        $order->update($orderUpdate);
                        return back()->with('success', 'Carrier status synchronized successfully: ' . ucfirst($status));
                    }
                    return back()->with('error', 'No tracking info returned by Shiprocket.');
                }
                return back()->with('error', 'Shiprocket API failed: Status ' . $response->status());
            } catch (\Exception $e) {
                return back()->with('error', 'Sync failed: ' . $e->getMessage());
            }
        }
    }
}
?>