<script setup>
import { ref } from 'vue';
import axios from 'axios';
import AppLayout from '../../Layouts/AppLayout.vue';

const props   = defineProps({ products: Array });
const products = ref([...props.products]);

const defaultForm = () => ({
  name:                     '',
  sku:                      '',
  base_cost:                0,
  procurement_cost:         0,
  delivery_cost:            0,
  packaging_cost:           0,
  demand_multiplier:        1.0,
  season_multiplier:        1.15,
  operational_margin_percent: 15,
  currency:                 'BDT',
});

const form     = ref(defaultForm());
const editing  = ref(null);
const saving   = ref(false);
const flash    = ref('');
const flashErr = ref('');

function reset() {
  editing.value = null;
  form.value    = defaultForm();
}

function edit(p) {
  editing.value = p.id;
  form.value = {
    name:                     p.name,
    sku:                      p.sku,
    base_cost:                parseFloat(p.base_cost),
    procurement_cost:         parseFloat(p.procurement_cost),
    delivery_cost:            parseFloat(p.delivery_cost),
    packaging_cost:           parseFloat(p.packaging_cost),
    demand_multiplier:        parseFloat(p.demand_multiplier),
    season_multiplier:        parseFloat(p.season_multiplier),
    operational_margin_percent: parseFloat(p.operational_margin_percent),
    currency:                 p.currency,
  };
}

async function save() {
  saving.value  = true;
  flash.value   = '';
  flashErr.value = '';
  try {
    const url    = editing.value ? `/admin/products/${editing.value}` : '/admin/products';
    const method = editing.value ? 'put' : 'post';
    await axios[method](url, form.value);
    flash.value = editing.value ? 'Product updated.' : 'Product created.';
    reset();
    await reload();
  } catch (e) {
    const errs = e.response?.data?.errors;
    flashErr.value = errs ? Object.values(errs).flat().join(' ') : (e.response?.data?.message ?? 'Save failed.');
  } finally {
    saving.value = false;
  }
}

async function remove(id) {
  if (!confirm('Delete this product?')) return;
  try {
    await axios.delete(`/admin/products/${id}`);
    flash.value = 'Product deleted.';
    await reload();
  } catch (e) {
    flashErr.value = 'Delete failed.';
  }
}

async function reload() {
  const res = await axios.get('/api/pricing/products');
  products.value = res.data;
}
</script>

<template>
  <AppLayout>
    <!-- Hero -->
    <div class="hero fade-in" style="margin-bottom:1.5rem">
      <div class="hero-inner">
        <span class="eyebrow">📦 Admin</span>
        <h1>Product Catalogue</h1>
        <p>
          Manage products and their base cost parameters. These values feed directly
          into the pricing engine calculation.
        </p>
      </div>
    </div>

    <!-- Flash -->
    <div v-if="flash"    class="alert alert-success fade-in" style="margin-bottom:1rem">✓ {{ flash }}</div>
    <div v-if="flashErr" class="alert alert-error fade-in"   style="margin-bottom:1rem">⚠ {{ flashErr }}</div>

    <!-- Form card -->
    <div class="rule-form-card fade-in">
      <div class="row" style="margin-bottom:1.25rem">
        <h2>{{ editing ? '✏ Edit Product' : '+ New Product' }}</h2>
        <div style="display:flex;gap:0.5rem">
          <button v-if="editing" @click="reset">Cancel</button>
          <button class="primary" @click="save" :disabled="saving" id="save-product-btn">
            <span v-if="saving" class="spinner" style="width:14px;height:14px"></span>
            {{ saving ? 'Saving…' : (editing ? 'Update' : 'Create Product') }}
          </button>
        </div>
      </div>

      <div class="form-row" style="grid-template-columns:1fr 1fr auto;margin-bottom:1rem">
        <label>Product name <input id="product-name" v-model="form.name" type="text" placeholder="e.g. Organic Rice" /></label>
        <label>SKU          <input id="product-sku"  v-model="form.sku"  type="text" placeholder="SX-RICE-001" :disabled="!!editing" /></label>
        <label>Currency     <input id="product-currency" v-model="form.currency" type="text" style="width:70px" placeholder="BDT" /></label>
      </div>

      <div class="form-section-title">Cost Components (per unit)</div>
      <div class="form-row">
        <label>Base cost          <input id="base-cost"        v-model.number="form.base_cost"                  type="number" min="0" step="0.01" /></label>
        <label>Procurement cost   <input id="procurement-cost" v-model.number="form.procurement_cost"           type="number" min="0" step="0.01" /></label>
        <label>Delivery cost      <input id="delivery-cost"    v-model.number="form.delivery_cost"              type="number" min="0" step="0.01" /></label>
        <label>Packaging cost     <input id="packaging-cost"   v-model.number="form.packaging_cost"             type="number" min="0" step="0.01" /></label>
        <label>Operational margin % <input id="op-margin"      v-model.number="form.operational_margin_percent" type="number" min="0" step="0.1"  /></label>
      </div>

      <div class="form-section-title" style="margin-top:1rem">Multipliers</div>
      <div class="form-row" style="grid-template-columns:1fr 1fr">
        <label data-tooltip="Default demand multiplier (overridable in API call)">
          Demand multiplier
          <input id="demand-mult" v-model.number="form.demand_multiplier" type="number" min="0.1" max="5" step="0.01" />
        </label>
        <label data-tooltip="Applied when season=peak">
          Peak season multiplier
          <input id="season-mult" v-model.number="form.season_multiplier" type="number" min="0.1" max="5" step="0.01" />
        </label>
      </div>
    </div>

    <!-- Products table -->
    <div class="card fade-in" style="animation-delay:0.1s">
      <div class="card-header">
        <h2>Products ({{ products.length }})</h2>
        <a href="/" style="font-size:0.85rem;color:var(--accent)">→ Test pricing</a>
      </div>

      <div v-if="products.length" class="table-wrap">
        <table id="products-table">
          <thead>
            <tr>
              <th>Name / SKU</th>
              <th>Base</th>
              <th>Proc.</th>
              <th>Delivery</th>
              <th>Pkg.</th>
              <th>Margin %</th>
              <th>Demand ×</th>
              <th>Season ×</th>
              <th>Currency</th>
              <th style="text-align:right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in products" :key="p.id">
              <td>
                <div style="font-weight:600">{{ p.name }}</div>
                <div style="font-size:0.75rem;color:var(--text-3)">{{ p.sku }}</div>
              </td>
              <td>{{ parseFloat(p.base_cost).toFixed(2) }}</td>
              <td>{{ parseFloat(p.procurement_cost).toFixed(2) }}</td>
              <td>{{ parseFloat(p.delivery_cost).toFixed(2) }}</td>
              <td>{{ parseFloat(p.packaging_cost).toFixed(2) }}</td>
              <td>{{ parseFloat(p.operational_margin_percent).toFixed(1) }}%</td>
              <td>{{ parseFloat(p.demand_multiplier).toFixed(2) }}</td>
              <td>{{ parseFloat(p.season_multiplier).toFixed(2) }}</td>
              <td><code>{{ p.currency }}</code></td>
              <td>
                <div class="td-actions">
                  <button class="btn-sm" @click="edit(p)">Edit</button>
                  <button class="btn-sm danger" @click="remove(p.id)">Delete</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="empty-state">
        <div class="empty-icon">📦</div>
        <p>No products yet. Create one above.</p>
      </div>
    </div>
  </AppLayout>
</template>
