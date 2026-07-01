# API Documentation Reference - GST Changes (CGST, SGST, IGST)

This reference document outlines the recent updates in API endpoints regarding the GST (CGST, SGST, IGST) percentages and tax breakdown. These parameters are crucial for tax transparency in checkout summaries, invoice generation, and product detail screens.

---

## 1. Product APIs

### `GET /api/products` (Product Listing) & `GET /api/products/{slug}` (Product Details)
Exposes the configured GST percentages for each product.

#### Response Structure:
```json
{
  "status": "success",
  "data": {
    "product": {
      "id": 1,
      "name": "Super Car Wax",
      "slug": "super-car-wax",
      "price": 499.00,
      "compare_price": 599.00,
      "discount_percentage": 16.69,
      "cgst": 9.00,  // <-- Added CGST rate in percentage
      "sgst": 9.00,  // <-- Added SGST rate in percentage
      "igst": 18.00, // <-- Added IGST rate in percentage
      "quantity": 42,
      "stock_status": "in_stock"
      // ...other properties
    }
  }
}
```

---

## 2. Cart APIs

### `GET /api/cart`
Exposes the individual product's GST parameters inside the cart response, helping you show potential tax rates in the cart summary page.

#### Response Structure:
```json
{
  "status": "success",
  "data": {
    "cart_items": [
      {
        "id": 12,
        "quantity": 2,
        "price": 499.00,
        "total": 998.00,
        "product": {
          "id": 1,
          "name": "Super Car Wax",
          "sku": "SCW-001",
          "price": 499.00,
          "cgst": 9.00,  // <-- Added CGST rate in percentage
          "sgst": 9.00,  // <-- Added SGST rate in percentage
          "igst": 18.00  // <-- Added IGST rate in percentage
          // ...other properties
        }
      }
    ],
    "summary": {
      "items_count": 1,
      "total_quantity": 2,
      "subtotal": 998.00
    }
  }
}
```

---

## 3. Order Checkout & Payment APIs

### `POST /api/payment/create-order` (Razorpay Checkout) & `POST /api/cart/checkout` (COD Checkout)
These checkout APIs dynamically calculate the tax breakdown and store CGST, SGST, or IGST based on the customer's state code.

#### Key Payload Requirement:
Make sure to send the `state` name under the `shipping_address` parameter so the server can verify if the order is **Intrastate** (CGST + SGST) or **Interstate** (IGST).

```json
{
  "shipping_address": {
    "name": "John Doe",
    "phone": "9876543210",
    "address_line_1": "123 Street Name",
    "city": "Mumbai",
    "state": "Maharashtra", // <-- State name is mandatory for tax determination (case-insensitive)
    "postal_code": "400001",
    "country": "India",
    "company_name": "Doe Enterprises", // Optional
    "gstin_number": "27AAAAA0000A1Z5"  // Optional customer GSTIN
  },
  "coupon_code": "DISCOUNT10"
}
```

---

## 4. Order Details & History APIs

### `GET /api/orders/{id}` & `GET /api/orders/history`
Exposes the calculated total tax and individual tax breakdown amounts stored in the database.

#### Response Structure:
```json
{
  "status": "success",
  "data": {
    "order": {
      "id": 15,
      "order_number": "ORD-6FCA298E7B12",
      "status": "processing",
      "payment_status": "completed",
      "subtotal": 998.00,          // Gross item total (inclusive of tax)
      "discount": 99.80,           // Coupon discount
      "shipping_cost": 0.00,
      "tax": 137.02,               // <-- Total GST Included in final amount
      "cgst_amount": 68.51,        // <-- Total CGST amount calculated
      "sgst_amount": 68.51,        // <-- Total SGST amount calculated
      "igst_amount": 0.00,         // <-- Total IGST amount calculated (if Interstate, this would have value)
      "total_amount": 898.20,      // Final paid total (inclusive of tax, shipping, minus discounts)
      "items": [
        {
          "id": 25,
          "product_name": "Super Car Wax",
          "quantity": 2,
          "price": 499.00,
          "subtotal": 998.00,
          "cgst_percent": 9.00,    // <-- Stored CGST rate % at the time of purchase
          "sgst_percent": 9.00,    // <-- Stored SGST rate % at the time of purchase
          "igst_percent": 0.00,    // <-- Stored IGST rate % at the time of purchase
          "cgst_amount": 68.51,    // <-- Calculated item CGST amount
          "sgst_amount": 68.51,    // <-- Calculated item SGST amount
          "igst_amount": 0.00,     // <-- Calculated item IGST amount
          "tax_amount": 137.02     // <-- Total tax amount calculated for this item
        }
      ]
    }
  }
}
```

#### Tax Allocation Calculation (Reference):
* Item tax is calculated on the **discounted net value** of the item.
* Example:
  * Product Price = ₹499 (tax-inclusive 18% GST). Quantity = 2.
  * Subtotal = ₹998.
  * Discount = ₹99.80 (proportionally allocated). Net Amount = ₹898.20.
  * Intrastate tax calculation (Maharashtra):
    * Combined Tax % = 9% CGST + 9% SGST = 18%.
    * Taxable Value (Base) = ₹898.20 / (1 + 0.18) = ₹761.18.
    * CGST Amount = ₹761.18 * 9% = ₹68.51.
    * SGST Amount = ₹761.18 * 9% = ₹68.51.
    * Total Tax = ₹137.02.
