# Source X Dynamic Pricing Engine — Technical Architecture

## 1. Overview
The Source X Dynamic Pricing Engine is built with **Laravel 11** on the backend and **Vue 3 + Inertia.js** on the frontend. The core tenet of the architecture is that **all pricing calculations are strictly backend-driven**. The client only supplies parameters, while the server executes business rules, applies multipliers, and produces the final audited price.

## 2. Core Components

### 2.1 Domain Models
- **`App\Models\Product`**:
  - Encapsulates unit cost factors: `base_cost`, `procurement_cost`, `delivery_cost`, `packaging_cost`.
  - Encapsulates product multipliers: `demand_multiplier`, `season_multiplier`, `operational_margin_percent`.
- **`App\Models\PricingRule`**:
  - Represents configurable business rules.
  - Fields: `name`, `priority`, `active`, `conditions` (JSON), `action_type`, `action_value`.
  - Condition Operators: `quantity_gte`, `quantity_gt`, `quantity_lte`, `customer_type`, `season`.
  - Action Types: `percent_discount`, `percent_markup`, `fixed_unit_price`.

### 2.2 Pricing Engine Service (`App\Services\PricingEngine`)
The single source of truth for price calculations:
1. **Base Cost Summation**:
   $$\text{unit\_cost} = \text{base} + \text{procurement} + \text{delivery} + \text{packaging}$$
2. **Market Multipliers**:
   $$\text{cost\_pre\_margin} = \text{unit\_cost} \times \text{demand\_multiplier} \times \text{season\_multiplier}$$
3. **Operational Margin**:
   $$\text{unit\_price} = \text{cost\_pre\_margin} \times \left(1 + \frac{\text{operational\_margin\_percent}}{100}\right)$$
4. **Rule Pipeline**:
   - Queries all active rules sorted by `priority DESC, id ASC`.
   - Checks if order inputs satisfy all rule conditions.
   - Updates `unit_price` sequentially.
   - Logs an audit entry containing rule ID, rule name, action, and before/after values.
5. **Output Guarantee**:
   - Prevents negative prices with `max(0, round(unit_price, 2))`.
   - Returns full breakdown of calculations and applied rules.

### 2.3 Controllers & Endpoints
- **`App\Http\Controllers\PricingApiController`**:
  - `POST /api/pricing/calculate`: Computes price and breakdown.
  - `GET /api/pricing/products/{product}`: Single product info.
  - `GET /api/pricing/rules`: List all active rules.
- **`App\Http\Controllers\PricingRuleController`**:
  - Full CRUD operations for admin rules management.
- **`App\Http\Controllers\ProductController`**:
  - Full CRUD operations for product catalogue.
- **`App\Http\Controllers\PricingPageController`**:
  - Renders Inertia Vue pages (`PricingDemo`, `Admin/PricingRules`, `Admin/Products`).

## 3. Database Schema
- SQLite (default) / MySQL / PostgreSQL compatible via standard Eloquent migrations.
- `products` table stores cost structures per SKU.
- `pricing_rules` table stores serialized condition JSON and execution parameters.
