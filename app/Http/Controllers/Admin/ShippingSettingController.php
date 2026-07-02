<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShippingSettingController extends Controller
{
    public function index()
    {
        $parcelxWarehouses = [];
        $parcelxToken = trim(setting('parcelx_access_token'));

        if ($parcelxToken) {
            try {
                $response = Http::withHeaders([
                    'access-token' => $parcelxToken,
                    'Content-Type' => 'application/json'
                ])->get('https://app.parcelx.in/api/v3/warehouse-list');

                if ($response->successful()) {
                    $body = $response->json();
                    if (isset($body['status']) && $body['status'] && isset($body['data'])) {
                        $parcelxWarehouses = $body['data'];
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to fetch ParcelX warehouses: ' . $e->getMessage());
            }
        }

        return view('admin.settings.shipping', compact('parcelxWarehouses'));
    }

    public function update(Request $request)
    {
        foreach ($request->except('_token') as $key => $value) {
            setting([$key => $value])->save();
        }

        return back()->with('success', 'Shipping settings updated successfully.');
    }

    public function createWarehouse(Request $request)
    {
        $parcelxToken = trim(setting('parcelx_access_token'));
        if (!$parcelxToken) {
            return back()->with('error', 'Please configure and save your Access Token first.');
        }

        $request->validate([
            'address_title' => 'required|string|max:100',
            'sender_name' => 'required|string|max:100',
            'full_address' => 'required|string|max:250',
            'phone' => 'required|string|max:15',
            'pincode' => 'required|string|size:6',
        ]);

        try {
            $response = Http::withHeaders([
                'access-token' => $parcelxToken,
                'Content-Type' => 'application/json'
            ])->post('https://app.parcelx.in/api/v3/create_warehouse', [
                'address_title' => $request->address_title,
                'sender_name' => $request->sender_name,
                'full_address' => $request->full_address,
                'phone' => $request->phone,
                'pincode' => $request->pincode,
            ]);

            $body = $response->json();

            if ($response->successful()) {
                if (isset($body['status']) && $body['status']) {
                    $pickupId = $body['data']['pick_address_id'] ?? null;
                    if ($pickupId) {
                        setting(['parcelx_pickup_location' => $pickupId])->save();
                    }

                    // If replacing an old warehouse, delete it from ParcelX
                    if ($request->filled('delete_on_success_id')) {
                        try {
                            Http::withHeaders([
                                'access-token' => $parcelxToken,
                                'Content-Type' => 'application/json'
                            ])->post('https://app.parcelx.in/api/v3/remove-warehouse', [
                                'warehouse_id' => (string)$request->delete_on_success_id
                            ]);
                        } catch (\Exception $ex) {
                            Log::error('Failed to remove old warehouse during replacement: ' . $ex->getMessage());
                        }
                    }

                    return back()->with('success', 'Warehouse saved successfully! Pickup ID: ' . ($pickupId ?? 'N/A'));
                } else {
                    return back()->with('error', $body['responsemsg'] ?? 'Failed to create warehouse.');
                }
            }

            $errorMessage = $body['responsemsg'] ?? $response->body() ?? 'API Request failed';
            return back()->with('error', 'API Request failed with status code: ' . $response->status() . ' — ' . $errorMessage);

        } catch (\Exception $e) {
            Log::error('Failed to create ParcelX warehouse: ' . $e->getMessage());
            return back()->with('error', 'An exception occurred: ' . $e->getMessage());
        }
    }

    public function deleteWarehouse($id)
    {
        $parcelxToken = trim(setting('parcelx_access_token'));
        if (!$parcelxToken) {
            return back()->with('error', 'Please configure and save your Access Token first.');
        }

        try {
            $response = Http::withHeaders([
                'access-token' => $parcelxToken,
                'Content-Type' => 'application/json'
            ])->post('https://app.parcelx.in/api/v3/remove-warehouse', [
                'warehouse_id' => (string)$id
            ]);

            $body = $response->json();

            if ($response->successful()) {
                // Clear active pickup location if it was the deleted warehouse
                if ((string)setting('parcelx_pickup_location') === (string)$id) {
                    setting(['parcelx_pickup_location' => null])->save();
                }
                return back()->with('success', $body['responsemsg'] ?? 'Warehouse deleted successfully.');
            }

            $errorMessage = $body['responsemsg'] ?? $response->body() ?? 'API Request failed';
            return back()->with('error', 'Delete failed: ' . $errorMessage);

        } catch (\Exception $e) {
            Log::error('Failed to delete ParcelX warehouse: ' . $e->getMessage());
            return back()->with('error', 'An exception occurred: ' . $e->getMessage());
        }
    }
}
