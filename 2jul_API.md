# 2nd July API Integration & Next.js Frontend Guide

This document summarizes the newly added order cancellation and return (RMA) APIs, including their requirements, endpoints, payloads, and corresponding frontend changes needed in your Next.js clientside app.

---

## 1. Order Cancellation API

Updates cancellation behavior for paid orders (if they are not shipped yet) with automatic Razorpay refund trigger and courier booking cancellation.

*   **Endpoint**: `POST /api/orders/{id}/cancel`
*   **Authentication**: Protected (Sanctum auth token required)
*   **Permissions/Logic**:
    *   Unpaid orders can be cancelled anytime.
    *   Paid orders can **only** be cancelled if `shipping_status` is **not** `'shipped'`, `'delivered'`, or `'rto'`.
*   **Request Headers**:
    ```json
    {
      "Authorization": "Bearer <sanctum_token>",
      "Accept": "application/json"
    }
    ```
*   **Response Payload**:
    ```json
    {
      "status": "success",
      "message": "Order cancelled and refund initiated successfully."
    }
    ```

### 💻 Next.js Frontend Changes:
1.  **Conditional Render**:
    *   Show the **"Cancel Order"** button *only* if:
        *   `order.status !== 'cancelled'` AND
        *   (`order.payment_status === 'pending'` OR (`order.payment_status === 'completed'` && `!['shipped', 'delivered', 'rto'].includes(order.shipping_status)`))
2.  **API Call Handler**:
    *   Trigger `POST /api/orders/{orderId}/cancel` on click.
    *   Refresh the page/state upon receipt of the success message to show the updated `'cancelled'` status.

---

## 2. Order Return Request (RMA) API

Submits return/refund requests for individual line-items of delivered orders.

*   **Endpoint**: `POST /api/orders/{id}/return`
*   **Authentication**: Protected (Sanctum auth token required)
*   **Permissions/Logic**:
    *   Can only be requested if order is `'completed'` or `shipping_status === 'delivered'`.
    *   Must be submitted within the **7-day return window** from the delivery timestamp.
*   **Request Headers**:
    ```json
    {
      "Authorization": "Bearer <sanctum_token>",
      "Content-Type": "application/json",
      "Accept": "application/json"
    }
    ```
*   **Request Payload**:
    ```json
    {
      "reason": "Wrong product option or items damaged in transit",
      "items": [
        {
          "order_item_id": 42,
          "quantity": 1
        }
      ]
    }
    ```
*   **Response Payload**:
    ```json
    {
      "status": "success",
      "message": "Return request submitted successfully. A courier pickup will be scheduled upon approval.",
      "data": {
        "return_request_id": 5,
        "refund_amount": 2245.00
      }
    }
    ```

### 💻 Next.js Frontend Changes:
1.  **Conditional Render**:
    *   Show a **"Request Return"** button *only* if:
        *   `order.shipping_status === 'delivered'` AND
        *   Delivery date is within the last 7 days (`now() - deliveryDate <= 7 days`).
2.  **Return Request Modal / Form**:
    *   Send the payload structure above to the `POST /api/orders/{id}/return` endpoint.
    *   Show a success popup informing them that the reverse courier pickup is pending.

---

## 3. Expected Delivery Date & Tracking Details

In addition to the raw payloads, the **Get Order Details API** (`GET /api/orders/{id}`) now flattens and returns clean, top-level parameters directly in the JSON response:

*   **`expected_delivery_date`**: The date when the shipment is expected to arrive.
    *   **Value**: String (e.g. `"2026-07-06 18:30:00"` or `"2026-07-05"`) or `null` (if not shipped/available yet).
*   **`tracking_url`**: Dynamic customer-facing tracking page.
    *   **Value**: String (e.g. `"https://app.parcelx.in/track?awb=..."` or `"https://shiprocket.co/tracking/..."`) or `null`.

### 💻 Next.js Frontend Changes:
You can directly read these fields from the order object response without manually parsing the JSON of `shipment_data`:
```typescript
// Fetching order details:
const res = await fetch(`/api/orders/${orderId}`);
const { data } = await res.json();
const order = data.order;

console.log(order.expected_delivery_date); // e.g. "2026-07-05"
console.log(order.tracking_url);           // e.g. "https://app.parcelx.in/track?awb=..."
```

---

## 4. Product Refundable & Cancellable API Flags

We have added product-level toggles to control which items can be cancelled or returned.

### 🗄️ Database Changes (Run this first):
```sql
ALTER TABLE `products` ADD COLUMN `is_refundable` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `products` ADD COLUMN `is_cancellable` tinyint(1) NOT NULL DEFAULT '0';
```

### 📦 API Parameter Exposures:
1.  **Product List/Details APIs** (`/api/products` and `/api/products/{slug}`):
    *   **`is_refundable`**: Boolean (`true`/`false`)
    *   **`is_cancellable`**: Boolean (`true`/`false`)
2.  **Order Details API** (`/api/orders/{id}`):
    *   Exposed inside the `product` sub-object nested under each line-item:
        ```json
        {
          "id": 14,
          "product_name": "Car Polish",
          ...
          "product": {
            "id": 5,
            "name": "Car Polish",
            "slug": "car-polish",
            "is_refundable": true,
            "is_cancellable": false
          }
        }
        ```

### 🔒 Backend Validation Rules:
*   **Cancellation**: If a customer calls `POST /api/orders/{id}/cancel` on an order containing any item where `is_cancellable` is `false`, the request is rejected with:
    *   **HTTP Status**: `400 Bad Request`
    *   **Response**: `{"status": "error", "message": "Order cannot be cancelled because '[product_name]' is non-cancellable."}`
*   **Returns**: If a customer requests a return via `POST /api/orders/{id}/return` for an item where `is_refundable` is `false`, the request is rejected with:
    *   **HTTP Status**: `400 Bad Request`
    *   **Response**: `{"status": "error", "message": "Cannot return '[product_name]' because it is non-refundable."}`

