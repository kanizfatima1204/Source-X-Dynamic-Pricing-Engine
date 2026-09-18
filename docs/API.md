# Source X Dynamic Pricing Engine — API Documentation

Base URL: `http://localhost:8000`

---

## 1. Calculate Price
**Endpoint:** `POST /api/pricing/calculate`  
**Description:** Evaluates backend pricing rules and calculates the dynamic price for a product.

### Request Headers
- `Content-Type: application/json`
- `Accept: application/json`

### Request Body Parameters
| Field | Type | Required | Description |
|---|---|---|---|
| `product_id` | Integer | Yes | The ID of the product to price |
| `quantity` | Integer | Yes | Number of units (min: 1) |
| `customer_type` | String | No | `'standard'`, `'corporate'`, `'wholesale'` (default: `'standard'`) |
| `season` | String | No | `'normal'`, `'peak'`, `'off_peak'` (default: `'normal'`) |
| `demand` | Float | No | Explicit demand multiplier override (e.g., `1.15`) |

### Example Request
```json
{
  "product_id": 1,
  "quantity": 25,
  "customer_type": "corporate",
  "season": "normal"
}
```

### Example Response (200 OK)
```json
{
  "product_id": 1,
  "sku": "SX-RICE-001",
  "quantity": 25,
  "customer_type": "corporate",
  "season": "normal",
  "currency": "BDT",
  "breakdown": {
    "base_cost": 120,
    "procurement_cost": 18,
    "delivery_cost": 8,
    "packaging_cost": 4,
    "demand_multiplier": 1.05,
    "season_multiplier": 1,
    "operational_margin_percent": 18,
    "unit_cost_before_margin": 157.5
  },
  "unit_price": 164.67,
  "total_price": 4116.75,
  "applied_rules": [
    {
      "id": 2,
      "name": "Bulk 10+ units",
      "action_type": "percent_discount",
      "value": 5,
      "before": 185.85,
      "after": 176.56
    },
    {
      "id": 3,
      "name": "Corporate buyer",
      "action_type": "percent_discount",
      "value": 7,
      "before": 176.56,
      "after": 164.67
    }
  ]
}
```

---

## 2. List Active Rules
**Endpoint:** `GET /api/pricing/rules`  
**Description:** Fetches all rules currently defined in the system.

### Example Response (200 OK)
```json
[
  {
    "id": 1,
    "name": "Bulk 50+ units",
    "priority": 100,
    "active": 1,
    "conditions": {
      "quantity_gte": 50
    },
    "action_type": "percent_discount",
    "action_value": "10.00"
  },
  {
    "id": 3,
    "name": "Corporate buyer",
    "priority": 80,
    "active": 1,
    "conditions": {
      "customer_type": "corporate"
    },
    "action_type": "percent_discount",
    "action_value": "7.00"
  }
]
```

---

## 3. List Products
**Endpoint:** `GET /api/pricing/products`  
**Description:** Returns the list of available products with cost components.

### Example Response (200 OK)
```json
[
  {
    "id": 1,
    "name": "Organic Rice",
    "sku": "SX-RICE-001",
    "base_cost": "120.00",
    "procurement_cost": "18.00",
    "delivery_cost": "8.00",
    "packaging_cost": "4.00",
    "demand_multiplier": "1.05",
    "season_multiplier": "1.15",
    "operational_margin_percent": "18.00",
    "currency": "BDT"
  }
]
```

---

## 4. Get Single Product
**Endpoint:** `GET /api/pricing/products/{id}`  
**Description:** Returns details of a specific product by ID.
