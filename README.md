# Source X — Dynamic Pricing Engine

A production-grade, backend-driven dynamic pricing system prototype designed for **Source X**. 

The system computes real-time product prices factoring in **procurement, logistics, packaging, market demand, seasonal shifts, bulk quantity tiers, and customer classifications** (e.g. corporate buyers). All calculation logic and rules are strictly evaluated on the Laravel backend — the frontend maintains zero hardcoded formulas and serves purely as an interactive reactive client via Inertia.js & Vue 3.

---

## 🌟 Key Features

### 1. Multi-Factor Pricing Computation
Every product calculation executes through the core engine factoring in:
- **Base Cost**: Production or base unit cost.
- **Procurement Cost**: Sourcing and supplier acquisition costs.
- **Delivery Cost**: Freight and localized transport expenses.
- **Packaging Cost**: Material and protective handling costs.
- **Demand Multiplier**: Real-time surge or reduction factor (e.g., `1.05` for +5% demand pressure).
- **Season Multiplier**: Dynamic seasonal index (Normal `1.0`, Peak `season_multiplier`, Off-peak `0.95`).
- **Operational Margin**: Target business margin percentage applied before external rules.

### 2. Flexible Admin Rules Engine
Business admins can configure rules dynamically without redeploying code:
- **Condition Matching**:
  - Quantity thresholds (`quantity_gte`, `quantity_gt`, `quantity_lte`)
  - Customer classification (`customer_type = 'corporate'`, `'wholesale'`, `'standard'`)
  - Seasonal context (`season = 'peak'`, `'off_peak'`, `'normal'`)
- **Actions**:
  - `percent_discount`: e.g. 10% off for 50+ units, 7% off for corporate buyers.
  - `percent_markup`: e.g. +3% surge during peak festival seasons.
  - `fixed_unit_price`: Absolute price override per unit.
- **Priority & Sequential Chaining**: Higher priority rules execute first with full audit trails of intermediate price transitions.

### 3. Backend-Driven Architecture
- **Zero Frontend Business Logic**: Vue 3 / Inertia merely sends user selections and renders the calculation breakdown returned from the backend.
- **Auditability**: The calculation response returns an itemized breakdown of base costs, multipliers, margin, and every rule applied with `before` and `after` prices.

---

## 🏗 System Architecture

```
[ Frontend: Inertia.js + Vue 3 ]
       │
       │ POST /api/pricing/calculate (product_id, quantity, customer_type, season, demand)
       ▼
[ Laravel Controller: PricingApiController ]
       │
       ▼
[ PricingEngine Service ]
       │
       ├─► 1. Base Cost Calculation:
       │      unit_cost = (base + procurement + delivery + packaging) * demand * season
       │      cost_with_margin = unit_cost * (1 + margin / 100)
       │
       ├─► 2. Rule Evaluation:
       │      Fetch active rules ordered by priority DESC
       │      Evaluate conditions: quantity, customer_type, season
       │
       ├─► 3. Action Application:
       │      Sequential discount / markup / fixed override
       │
       └─► 4. Return Final Price & Full Audit Breakdown
```

---

## 🚀 Quick Start

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js 18+ and npm
- SQLite3 extension enabled in PHP

### Installation

1. **Clone and navigate to repository**:
   ```bash
   cd source-x-dynamic-pricing
   ```

2. **Setup environment & dependencies**:
   ```bash
   cp .env.example .env
   composer install
   php artisan key:generate
   ```

3. **Initialize Database & Seed Default Data**:
   ```bash
   php artisan migrate --seed
   ```

4. **Install & Build Frontend Assets**:
   ```bash
   npm install
   npm run build
   ```

5. **Start Application**:
   ```bash
   php artisan serve
   ```
   Access the web app at **[http://localhost:8000](http://localhost:8000)**.

---

## 🖥 Web Interface & Demo

The application comes with three full-featured reactive interfaces:

1. **Live Pricing Demo (`/`)**:
   - Interactive simulator allowing users to pick products, modify quantity sliders, switch customer types (Standard, Corporate, Wholesale), toggle seasons, and test demand multipliers.
   - Shows live unit price, total order price, cost component breakdown, and step-by-step rule execution tags.
2. **Pricing Rules Management (`/admin/rules`)**:
   - Admin UI to create, edit, toggle active status, adjust priority, and delete dynamic pricing rules with live condition builders.
3. **Product Catalogue Management (`/admin/products`)**:
   - Admin UI to manage product specifications, base procurement/delivery/packaging costs, target margin, and seasonal multipliers.

---

## 🔌 API Reference

### `POST /api/pricing/calculate`
Calculates dynamic price for a specific product and ordering scenario.

#### Request Body
```json
{
  "product_id": 1,
  "quantity": 50,
  "customer_type": "corporate",
  "season": "peak",
  "demand": 1.10
}
```

#### Response (200 OK)
```json
{
  "product_id": 1,
  "sku": "SX-RICE-001",
  "quantity": 50,
  "customer_type": "corporate",
  "season": "peak",
  "currency": "BDT",
  "breakdown": {
    "base_cost": 120,
    "procurement_cost": 18,
    "delivery_cost": 8,
    "packaging_cost": 4,
    "demand_multiplier": 1.1,
    "season_multiplier": 1.15,
    "operational_margin_percent": 18,
    "unit_cost_before_margin": 189.75
  },
  "unit_price": 190.58,
  "total_price": 9529.00,
  "applied_rules": [
    {
      "id": 1,
      "name": "Bulk 50+ units",
      "action_type": "percent_discount",
      "value": 10,
      "before": 223.91,
      "after": 201.52
    },
    {
      "id": 3,
      "name": "Corporate buyer",
      "action_type": "percent_discount",
      "value": 7,
      "before": 201.52,
      "after": 187.41
    }
  ]
}
```

---

## 🧪 Testing

Comprehensive test suites covering unit calculation logic and HTTP endpoints:

```bash
php artisan test
```

### Test Coverage Highlights:
- **Unit Tests (`Tests\Unit\PricingEngineTest`)**:
  - Zero and negative quantity validation exceptions
  - Base unit cost summation math
  - Seasonal multiplier resolution (Peak, Off-peak, Normal)
  - Condition evaluation: `quantity_gte`, `quantity_gt`, `quantity_lte`, `customer_type`, `season`, and multi-condition `AND` logic
- **Feature Tests (`Tests\Feature\PricingApiTest`)**:
  - Validation of request parameters
  - Bulk discount rule activation
  - Corporate buyer discount activation
  - Peak season markup rules
  - Inactive rule filtering
  - Demand overrides
  - Fixed unit price overrides
  - Full CRUD lifecycle for admin rule endpoints

---

## 📄 License
Open-source software licensed under the MIT License.
