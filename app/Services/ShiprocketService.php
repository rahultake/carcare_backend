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

        // Decode billing/shipping JSON stored in DB
        $billing = json_decode($order->billing_address, true);
        $shipping = json_decode($order->shipping_address, true);

        // Load all order items from order_items table
        $items = [];
        foreach ($order->items as $item) {
            $items[] = [
                "name"          => $item->product_name,
                "sku"           => $item->product_sku,
                "units"         => $item->quantity,
                "selling_price" => $item->price
            ];
        }

        $payload = [
            "order_id"              => $order->order_number,
            "order_date"            => now()->format('Y-m-d H:i:s'),
            "pickup_location"       => env('SHIPROCKET_PICKUP_LOCATION', 'work'),

            // Billing
            "billing_customer_name" => $billing["name"],
            "billing_last_name"     => "",
            "billing_address"       => $billing["address_line_1"],
            "billing_city"          => $billing["city"],
            "billing_state"         => $billing["state"],
            "billing_pincode"       => $billing["postal_code"],
            "billing_country"       => $billing["country"],
            "billing_email"         => $order->user->email,
            "billing_phone"         => $billing["phone"],

            "shipping_is_billing"   => true,
            "order_items"           => $items,

            "payment_method"        => "Prepaid",
            "sub_total"             => $order->subtotal,

            // required by shiprocket
            "length" => 10,
            "breadth" => 10,
            "height" => 10,
            "weight" => 0.40
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
}
