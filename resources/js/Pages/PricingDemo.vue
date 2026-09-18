<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import AppLayout from '../Layouts/AppLayout.vue';

const props = defineProps({ products: Array });

const productId   = ref(props.products[0]?.id ?? null);
const quantity    = ref(5);
const customerType = ref('standard');
const season      = ref('normal');
const demand      = ref(null);
const result      = ref(null);
const loading     = ref(false);
const error       = ref('');

const selectedProduct = computed(() =>
  props.products.find(p => p.id === productId.value) ?? null
);

async function calculate() {
  if (!productId.value) return;
  loading.value = true;
  error.value   = '';
  try {
    const payload = {
      product_id:    productId.value,
      quantity:      quantity.value,
      customer_type: customerType.value,
      season:        season.value,
    };
    if (demand.value !== null && demand.value !== '') {
      payload.demand = parseFloat(demand.value);
    }
    result.value = (await axios.post('/api/pricing/calculate', payload)).data;
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Calculation failed. Check your inputs.';
  } finally {
    loading.value = false;
  }
}

let debounceTimer = null;
function debouncedCalculate() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    calculate();
  }, 200);
}

watch([productId, quantity, customerType, season, demand], debouncedCalculate);
calculate();

// helpers
function formatAction(rule) {
  if (rule.action_type === 'percent_discount') return `–${rule.value}% discount`;
  if (rule.action_type === 'percent_markup')   return `+${rule.value}% markup`;
  return `Fixed ${rule.value}`;
}

function actionClass(rule) {
  if (rule.action_type === 'percent_discount') return 'rule-effect';
  if (rule.action_type === 'percent_markup')   return 'rule-effect markup';
  return 'rule-effect';
}

function saving(rule) {
  return (rule.before - rule.after).toFixed(2);
}
</script>

<template>
  <AppLayout>
    <!-- Hero -->
    <div class="hero fade-in">
      <div class="hero-inner">
        <span class="eyebrow">⚡ Backend-driven prototype</span>
        <h1>Dynamic Pricing Engine</h1>
        <p>
          All pricing logic lives on the Laravel backend. Vue only sends your inputs
          and renders the calculated result — no hardcoded formulas in the frontend.
        </p>
        <div class="hero-badges">
          <span class="badge badge-blue">Backend Rules Engine</span>
          <span class="badge badge-purple">Inertia.js + Vue 3</span>
          <span class="badge badge-green">Real-time Calculation</span>
        </div>
      </div>
    </div>

    <!-- Main content grid -->
    <div class="grid grid-2" style="gap:1.5rem; align-items: start;">

      <!-- Inputs card -->
      <div class="card fade-in" style="animation-delay:0.05s">
        <div class="card-header">
          <div>
            <h2>Pricing Inputs</h2>
            <p style="font-size:0.85rem;color:var(--text-2);margin-top:0.25rem">
              Adjust the parameters below — price recalculates instantly.
            </p>
          </div>
          <div v-if="loading" class="spinner"></div>
        </div>

        <div class="form-section">
          <div class="form-section-title">Product</div>
          <label>
            Select product
            <select id="product-select" v-model="productId">
              <option v-for="p in products" :key="p.id" :value="p.id">
                {{ p.name }} — {{ p.sku }}
              </option>
            </select>
          </label>
        </div>

        <div class="form-section">
          <div class="form-section-title">Order Details</div>
          <div class="form-row">
            <label>
              Quantity
              <input id="quantity-input" v-model.number="quantity" type="number" min="1" max="9999" />
            </label>
            <label>
              Customer Type
              <select id="customer-type-select" v-model="customerType">
                <option value="standard">Standard</option>
                <option value="corporate">Corporate</option>
                <option value="wholesale">Wholesale</option>
              </select>
            </label>
          </div>
        </div>

        <div class="form-section">
          <div class="form-section-title">Market Conditions</div>
          <div class="form-row">
            <label>
              Season
              <select id="season-select" v-model="season">
                <option value="normal">Normal</option>
                <option value="peak">Peak</option>
                <option value="off_peak">Off-peak</option>
              </select>
            </label>
            <label data-tooltip="Override product's default demand multiplier (0.5–3.0)">
              Demand override
              <input id="demand-input" v-model="demand" type="number" min="0.5" max="3" step="0.05" placeholder="auto" />
            </label>
          </div>
        </div>

        <!-- Product base info -->
        <div v-if="selectedProduct" class="form-section" style="margin-bottom:0">
          <div class="form-section-title">Product base costs (read-only)</div>
          <div class="breakdown-grid" style="margin:0">
            <dt>Base cost</dt>      <dd>{{ selectedProduct.currency }} {{ parseFloat(selectedProduct.base_cost).toFixed(2) }}</dd>
            <dt>Procurement</dt>   <dd>{{ parseFloat(selectedProduct.procurement_cost).toFixed(2) }}</dd>
            <dt>Delivery</dt>      <dd>{{ parseFloat(selectedProduct.delivery_cost).toFixed(2) }}</dd>
            <dt>Packaging</dt>     <dd>{{ parseFloat(selectedProduct.packaging_cost).toFixed(2) }}</dd>
            <dt>Margin %</dt>      <dd>{{ parseFloat(selectedProduct.operational_margin_percent).toFixed(2) }}%</dd>
          </div>
        </div>

        <div v-if="error" class="alert alert-error" style="margin-top:1rem">
          ⚠ {{ error }}
        </div>
      </div>

      <!-- Result card -->
      <div class="card glow-pulse fade-in" style="animation-delay:0.1s" v-if="result">
        <div class="card-header" style="margin-bottom:1rem">
          <h2>Calculated Price</h2>
          <span class="badge badge-green">Live</span>
        </div>

        <!-- Unit price -->
        <div class="price-main">
          <span class="price-currency">{{ result.currency }}</span>
          <span class="price-amount">{{ result.unit_price.toFixed(2) }}</span>
          <span class="price-unit">/ unit</span>
        </div>
        <div class="price-total">
          Total for {{ result.quantity }} unit{{ result.quantity !== 1 ? 's' : '' }}:
          <strong>{{ result.currency }} {{ result.total_price.toFixed(2) }}</strong>
        </div>

        <hr />

        <!-- Stat pills -->
        <div class="stat-row">
          <div class="stat-pill">
            <div class="stat-label">Unit Price</div>
            <div class="stat-value accent">{{ result.unit_price.toFixed(2) }}</div>
          </div>
          <div class="stat-pill">
            <div class="stat-label">Total Price</div>
            <div class="stat-value green">{{ result.total_price.toFixed(2) }}</div>
          </div>
          <div class="stat-pill">
            <div class="stat-label">Rules Applied</div>
            <div class="stat-value">{{ result.applied_rules.length }}</div>
          </div>
        </div>

        <hr />

        <!-- Breakdown -->
        <h3 style="margin-bottom:0.75rem">Cost Breakdown</h3>
        <div class="breakdown-grid">
          <dt>Base cost</dt>
          <dd>{{ result.breakdown.base_cost.toFixed(2) }}</dd>
          <dt>Procurement</dt>
          <dd>{{ result.breakdown.procurement_cost.toFixed(2) }}</dd>
          <dt>Delivery</dt>
          <dd>{{ result.breakdown.delivery_cost.toFixed(2) }}</dd>
          <dt>Packaging</dt>
          <dd>{{ result.breakdown.packaging_cost.toFixed(2) }}</dd>
          <dt>Demand multiplier</dt>
          <dd>× {{ result.breakdown.demand_multiplier }}</dd>
          <dt>Season multiplier</dt>
          <dd>× {{ result.breakdown.season_multiplier }}</dd>
          <dt>Operational margin</dt>
          <dd>{{ result.breakdown.operational_margin_percent }}%</dd>
          <dt style="font-weight:600;color:var(--text-1)">Unit cost (pre-rules)</dt>
          <dd style="font-weight:700;color:var(--accent)">{{ result.breakdown.unit_cost_before_margin.toFixed(2) }}</dd>
        </div>

        <hr />

        <!-- Applied rules -->
        <h3 style="margin-bottom:0.75rem">Backend Rules Applied</h3>
        <template v-if="result.applied_rules.length">
          <div v-for="r in result.applied_rules" :key="r.id" class="rule-chip">
            <div>
              <div class="rule-name">{{ r.name }}</div>
              <div style="font-size:0.75rem;color:var(--text-3)">
                {{ r.before.toFixed(2) }} → {{ r.after.toFixed(2) }}
              </div>
            </div>
            <div :class="actionClass(r)">{{ formatAction(r) }}</div>
          </div>
        </template>
        <p v-else class="no-rules">No pricing rules matched for these parameters.</p>
      </div>

      <!-- Loading placeholder -->
      <div class="card fade-in" style="animation-delay:0.1s;display:flex;align-items:center;justify-content:center;min-height:200px;color:var(--text-3)" v-else-if="loading">
        <div style="text-align:center">
          <div class="spinner" style="width:28px;height:28px;margin-bottom:1rem"></div>
          <p>Calculating price…</p>
        </div>
      </div>
    </div>

    <!-- How it works section -->
    <div class="card fade-in" style="margin-top:1.5rem;animation-delay:0.15s">
      <h2 style="margin-bottom:1rem">⚙ How the Pricing Engine Works</h2>
      <div class="grid grid-3">
        <div>
          <h3 style="color:var(--accent);margin-bottom:0.5rem">1. Base Calculation</h3>
          <p style="font-size:0.875rem;color:var(--text-2)">
            <code>unit_cost = (base + procurement + delivery + packaging) × demand × season</code><br/><br/>
            Then applies the operational margin percentage.
          </p>
        </div>
        <div>
          <h3 style="color:var(--accent);margin-bottom:0.5rem">2. Rule Engine</h3>
          <p style="font-size:0.875rem;color:var(--text-2)">
            Backend fetches all active rules ordered by priority.
            Each rule's <code>conditions</code> JSON is evaluated against your inputs
            (quantity, customer type, season).
          </p>
        </div>
        <div>
          <h3 style="color:var(--accent);margin-bottom:0.5rem">3. Price Adjustment</h3>
          <p style="font-size:0.875rem;color:var(--text-2)">
            Matching rules apply <code>percent_discount</code>, <code>percent_markup</code>,
            or <code>fixed_unit_price</code> actions sequentially.
            Frontend receives the final price — zero business logic here.
          </p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
