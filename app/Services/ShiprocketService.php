<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShiprocketService
{
    private $token;

    public function __construct()
    {
        $this->token = $this->generateToken();
    }

    private function generateToken()
    {
        try {
            $response = Http::post(env('SHIPROCKET_BASE_URL') . '/auth/login', [
                'email'    => env('SHIPROCKET_EMAIL'),
                'password' => env('SHIPROCKET_PASSWORD')
            ]);

            if ($response->failed()) {
                Log::error("Shiprocket Login Failed: " . $response->body());
                return null;
            }

            return $response->json()['token'] ?? null;

        } catch (\Exception $e) {
            Log::error("Shiprocket Login Exception: " . $e->getMessage());
            return null;
        }
    }

    private function headers()
    {
        if (!$this->token) {
            Log::error("Shiprocket Token Missing");
            return [];
        }

        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Content-Type'  => 'application/json'
        ];
    }

    public function createOrder($order)
    {
        if (!$this->token) {
            Log::error("Cannot create Shiprocket order — Token Missing");
            return null;
        }

        // Make sure items and products are eager loaded to avoid N+1 queries
        $order->loadMissing('items.product');

        // Decode billing and shipping JSON stored in DB
        $billing = json_decode($order->billing_address, true);
        $shipping = json_decode($order->shipping_address, true) ?: $billing;

        // Calculate dynamic dimensions and weight
        $maxLength = 0;
        $maxWidth = 0;
        $totalHeight = 0;
        $totalWeight = 0;

        // Load all order items from order_items table
        $items = [];
        foreach ($order->items as $item) {
            $items[] = [
                "name"          => $item->product_name,
                "sku"           => $item->product_sku,
                "units"         => $item->quantity,
                "selling_price" => $item->price
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
        $weight  = $totalWeight > 0 ? $totalWeight : 0.40;

        $payload = [
            "order_id"              => $order->order_number,
            "order_date"            => now()->format('Y-m-d H:i:s'),
            "pickup_location"       => env('SHIPROCKET_PICKUP_LOCATION', 'work'),

            // Billing
            "billing_customer_name" => $billing["name"],
            "billing_last_name"     => "",
            "billing_address"       => $billing["address_line_1"] . (!empty($billing["address_line_2"]) ? ", " . $billing["address_line_2"] : ""),
            "billing_city"          => $billing["city"],
            "billing_state"         => $billing["state"],
            "billing_pincode"       => $billing["postal_code"],
            "billing_country"       => $billing["country"],
            "billing_email"         => $order->user->email,
            "billing_phone"         => $billing["phone"],

            // Shipping
            "shipping_customer_name" => $shipping["name"],
            "shipping_last_name"     => "",
            "shipping_address"       => $shipping["address_line_1"] . (!empty($shipping["address_line_2"]) ? ", " . $shipping["address_line_2"] : ""),
            "shipping_city"          => $shipping["city"],
            "shipping_state"         => $shipping["state"],
            "shipping_pincode"       => $shipping["postal_code"],
            "shipping_country"       => $shipping["country"],
            "shipping_email"         => $order->user->email,
            "shipping_phone"         => $shipping["phone"],

            "shipping_is_billing"   => false,
            "order_items"           => $items,

            "payment_method"        => "Prepaid",
            "sub_total"             => $order->subtotal,

            // calculated dynamically
            "length"  => $length,
            "breadth" => $breadth,
            "height"  => $height,
            "weight"  => $weight
        ];

        try {
            $response = Http::withHeaders($this->headers())
                ->post(env('SHIPROCKET_BASE_URL') . '/orders/create/adhoc', $payload);

            Log::info("Shiprocket Order Payload: " . json_encode($payload));
            Log::info("Shiprocket Order Response: " . $response->body());

            return $response->json() ?? null;

        } catch (\Exception $e) {
            Log::error("Shiprocket Order Exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Cancel an active order on Shiprocket
     */
    public function cancelOrder($shiprocketOrderId)
    {
        if (!$this->token) {
            Log::error("Cannot cancel Shiprocket order — Token Missing");
            return null;
        }

        $payload = [
            "ids" => [(int)$shiprocketOrderId]
        ];

        try {
            $response = Http::withHeaders($this->headers())
                ->post(env('SHIPROCKET_BASE_URL') . '/orders/cancel', $payload);

            Log::info("Shiprocket Cancel Order Payload: " . json_encode($payload));
            Log::info("Shiprocket Cancel Order Response: " . $response->body());

            return $response->json() ?? null;

        } catch (\Exception $e) {
            Log::error("Shiprocket Cancel Order Exception: " . $e->getMessage());
            return null;
        }
    }
}
