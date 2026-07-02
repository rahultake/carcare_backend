<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ParcelXService
{
    private $baseUrl;
    private $accessToken;

    public function __construct()
    {
        $this->baseUrl = setting('parcelx_base_url', 'https://app.parcelx.in');
        $this->accessToken = trim(setting('parcelx_access_token'));
    }

    private function headers()
    {
        if (!$this->accessToken) {
            Log::error("ParcelX Access Token Missing");
            return [];
        }

        return [
            'access-token' => $this->accessToken,
            'Content-Type' => 'application/json'
        ];
    }

    /**
     * Sanitize string for ParcelX API (strips special characters, keeps alphanumeric and spaces)
     */
    private function sanitize($string, $maxLength = 200)
    {
        if (empty($string)) {
            return "";
        }
        // Strip special characters, allow alphanumeric and space
        $clean = preg_replace('/[^a-zA-Z0-9\s]/', ' ', $string);
        // Replace multiple spaces with a single space
        $clean = preg_replace('/\s+/', ' ', $clean);
        return substr(trim($clean), 0, $maxLength);
    }

    public function createOrder($order)
    {
        if (!$this->accessToken) {
            Log::error("Cannot create ParcelX order — Access Token Missing");
            return null;
        }

        // Eager load relationships to avoid N+1 queries
        $order->loadMissing('items.product.categories');

        // Decode billing and shipping JSON stored in DB
        $billing = json_decode($order->billing_address, true);
        $shipping = json_decode($order->shipping_address, true) ?: $billing;

        // Calculate dynamic dimensions and weight
        $maxLength = 0;
        $maxWidth = 0;
        $totalHeight = 0;
        $totalWeight = 0;

        $products = [];
        foreach ($order->items as $item) {
            // Get category details for HSN and naming
            $category = $item->product?->categories?->first();
            $hsn = $category?->hsn_code ?? "";
            $catName = $category?->name ?? "General";

            $products[] = [
                "product_sku"         => (string)($item->product_sku ?: $item->id),
                "product_name"        => $this->sanitize($item->product_name, 50),
                "product_value"       => (string)$item->price,
                "product_hsnsac"      => (string)$hsn,
                "product_taxper"      => 0,
                "product_category"    => $this->sanitize($catName, 30),
                "product_quantity"    => (string)$item->quantity,
                "product_description" => ""
            ];

            // Extract physical properties (with fallback values if not set)
            $pLength = (float)($item->product?->length ?: 10);
            $pWidth  = (float)($item->product?->width ?: 10);
            $pHeight = (float)($item->product?->height ?: 5);
            $pWeight = (float)($item->product?->weight ?: 0.1);

            $maxLength   = max($maxLength, $pLength);
            $maxWidth    = max($maxWidth, $pWidth);
            $totalHeight += $pHeight * $item->quantity;
            $totalWeight += $pWeight * $item->quantity;
        }

        // Apply final package dimension boundaries and fallbacks
        $length  = $maxLength > 0 ? $maxLength : 10;
        $breadth = $maxWidth > 0 ? $maxWidth : 10;
        $height  = $totalHeight > 0 ? $totalHeight : 10;
        
        // Weight in kg (default to 0.40 kg if less than 0)
        $weight  = $totalWeight > 0 ? $totalWeight : 0.40;

        $courierType = (int)setting('parcelx_courier_type', 1); // Default to manual selection to avoid Priority errors
        $courierCode = setting('parcelx_default_courier', 'PXDEL01'); // Default to Delhivery

        $payload = [
            "client_order_id"    => (string)$order->order_number,
            "pregenerated_awb"   => "",
            "consignee_emailid"  => (string)($order->user?->email ?? ""),
            "consignee_pincode"  => (string)$shipping["postal_code"],
            "consignee_mobile"   => (string)$shipping["phone"],
            "consignee_phone"    => "",
            "consignee_address1" => $this->sanitize($shipping["address_line_1"], 150),
            "consignee_address2" => $this->sanitize($shipping["address_line_2"] ?? "", 150),
            "consignee_name"     => $this->sanitize($shipping["name"], 100),
            "invoice_number"     => (string)$order->order_number,
            "express_type"       => "surface", // air or surface
            "pick_address_id"    => (string)setting('parcelx_pickup_location'),
            "return_address_id"  => "",
            "cod_amount"         => "0",
            "tax_amount"         => "0",
            "b2b"                => false,
            "mps"                => "0",
            "courier_type"       => $courierType,
            "courier_code"       => $courierType === 1 ? $courierCode : "",
            "products"           => $products,
            "payment_mode"       => "Prepaid",
            "order_amount"       => (string)($order->total_amount ?? $order->total),
            "extra_charges"      => "0",
            "shipment_width"     => [(string)$breadth],
            "shipment_height"    => [(string)$height],
            "shipment_length"    => [(string)$length],
            "shipment_weight"    => [(string)$weight]
        ];

        try {
            $endpoint = rtrim($this->baseUrl, '/') . '/api/v3/order/create_order';
            
            Log::info("ParcelX Order Request URL: " . $endpoint);
            Log::info("ParcelX Order Request Payload: " . json_encode($payload));
            
            $response = Http::withHeaders($this->headers())->post($endpoint, $payload);

            Log::info("ParcelX Order Response Body: " . $response->body());

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("ParcelX Order Creation Failed with status " . $response->status() . ": " . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::error("ParcelX Order Creation Exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Cancel an active order/shipment booking on ParcelX
     */
    public function cancelOrder($awbCode)
    {
        if (!$this->accessToken) {
            Log::error("Cannot cancel ParcelX order — Access Token Missing");
            return null;
        }

        $payload = [
            "awb" => (string)$awbCode
        ];

        try {
            $endpoint = rtrim($this->baseUrl, '/') . '/api/v3/order/cancel_order';
            
            Log::info("ParcelX Cancel Order Request URL: " . $endpoint);
            Log::info("ParcelX Cancel Order Request Payload: " . json_encode($payload));
            
            $response = Http::withHeaders($this->headers())->post($endpoint, $payload);

            Log::info("ParcelX Cancel Order Response Body: " . $response->body());

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("ParcelX Order Cancellation Failed with status " . $response->status() . ": " . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::error("ParcelX Order Cancellation Exception: " . $e->getMessage());
            return null;
        }
    }
}
