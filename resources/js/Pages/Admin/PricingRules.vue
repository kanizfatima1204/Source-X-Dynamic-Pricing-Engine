<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({ rules: Array });
const rules = ref([...props.rules]);

// ── condition key options ──
const conditionKeys = [
  { value: 'quantity_gte',  label: 'Quantity ≥' },
  { value: 'quantity_gt',   label: 'Quantity >' },
  { value: 'quantity_lte',  label: 'Quantity ≤' },
  { value: 'customer_type', label: 'Customer type =' },
  { value: 'season',        label: 'Season =' },
];
const customerTypeOptions = ['standard','corporate','wholesale'];
const seasonOptions       = ['normal','peak','off_peak'];

// ── form state ──
const defaultForm = () => ({
  name:         '',
  priority:     50,
  active:       true,
  action_type:  'percent_discount',
  action_value: 10,
  conditions:   [{ key: 'quantity_gte', value: '10' }],
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

function edit(r) {
  editing.value = r.id;
  const conds = Object.entries(r.conditions || {}).map(([k,v]) => ({ key: k, value: String(v) }));
  form.value = {
    name:         r.name,
    priority:     r.priority,
    active:       r.active,
    action_type:  r.action_type,
    action_value: parseFloat(r.action_value),
    conditions:   conds.length ? conds : [{ key: 'quantity_gte', value: '10' }],
  };
}

function addCondition() {
  form.value.conditions.push({ key: 'quantity_gte', value: '' });
}

function removeCondition(idx) {
  form.value.conditions.splice(idx, 1);
}

function buildConditionsPayload() {
  const obj = {};
  for (const c of form.value.conditions) {
    if (c.key && c.value !== '') {
      obj[c.key] = isNaN(c.value) ? c.value : Number(c.value);
    }
  }
  return obj;
}

async function save() {
  saving.value  = true;
  flash.value   = '';
  flashErr.value = '';
  try {
    const payload = {
      name:         form.value.name,
      priority:     form.value.priority,
      active:       form.value.active,
      action_type:  form.value.action_type,
      action_value: form.value.action_value,
      conditions:   buildConditionsPayload(),
    };
    const url    = editing.value ? `/admin/rules/${editing.value}` : '/admin/rules';
    const method = editing.value ? 'put' : 'post';
    await axios[method](url, payload);
    flash.value = editing.value ? 'Rule updated successfully.' : 'Rule created successfully.';
    reset();
    await reload();
  } catch (e) {
    flashErr.value = e.response?.data?.message ?? 'Save failed.';
  } finally {
    saving.value = false;
  }
}

async function remove(id) {
  if (!confirm('Delete this rule permanently?')) return;
  try {
    await axios.delete(`/admin/rules/${id}`);
    flash.value = 'Rule deleted.';
    await reload();
  } catch (e) {
    flashErr.value = 'Delete failed.';
  }
}

async function toggleActive(rule) {
  try {
    await axios.put(`/admin/rules/${rule.id}`, {
      name:         rule.name,
      priority:     rule.priority,
      active:       !rule.active,
      action_type:  rule.action_type,
      action_value: rule.action_value,
      conditions:   rule.conditions,
    });
    await reload();
  } catch {}
}

async function reload() {
  const res = await axios.get('/api/pricing/rules');
  rules.value = res.data;
}

function actionLabel(type) {
  if (type === 'percent_discount') return 'Discount %';
  if (type === 'percent_markup')   return 'Markup %';
  return 'Fixed price';
}

function actionClass(type) {
  if (type === 'percent_discount') return 'action-badge action-discount';
  if (type === 'percent_markup')   return 'action-badge action-markup';
  return 'action-badge action-fixed';
}

function formatConditions(conds) {
  return Object.entries(conds || {}).map(([k,v]) => `${k}: ${v}`);
}
</script>

<template>
  <AppLayout>
    <!-- Hero -->
    <div class="hero fade-in" style="margin-bottom:1.5rem">
      <div class="hero-inner">
        <span class="eyebrow">⚙ Admin Controls</span>
        <h1>Pricing Rules Manager</h1>
        <p>
          Configure backend rules that drive price calculations.
          Rules are evaluated by priority (highest first) — no frontend business logic.
        </p>
      </div>
    </div>

    <!-- Flash messages -->
    <div v-if="flash"    class="alert alert-success fade-in" style="margin-bottom:1rem">✓ {{ flash }}</div>
    <div v-if="flashErr" class="alert alert-error fade-in"   style="margin-bottom:1rem">⚠ {{ flashErr }}</div>

    <!-- Rule form card -->
    <div class="rule-form-card fade-in">
      <div class="row" style="margin-bottom:1.25rem">
        <h2>{{ editing ? '✏ Edit Rule' : '+ New Rule' }}</h2>
        <div style="display:flex;gap:0.5rem">
          <button v-if="editing" @click="reset" style="font-size:0.8rem">Cancel</button>
          <button class="primary" @click="save" :disabled="saving" id="save-rule-btn">
            <span v-if="saving" class="spinner" style="width:14px;height:14px"></span>
            {{ saving ? 'Saving…' : (editing ? 'Update Rule' : 'Create Rule') }}
          </button>
        </div>
      </div>

      <!-- Basic info -->
      <div class="form-row" style="grid-template-columns:1fr auto auto auto;gap:1rem;margin-bottom:1rem">
        <label>
          Rule name
          <input id="rule-name" v-model="form.name" type="text" placeholder="e.g. Bulk 50+ discount" />
        </label>
        <label>
          Priority
          <input id="rule-priority" v-model.number="form.priority" type="number" min="0" max="9999" style="width:90px" />
        </label>
        <label>
          Action type
          <select id="rule-action-type" v-model="form.action_type">
            <option value="percent_discount">Discount %</option>
            <option value="percent_markup">Markup %</option>
            <option value="fixed_unit_price">Fixed unit price</option>
          </select>
        </label>
        <label>
          Value
          <input id="rule-action-value" v-model.number="form.action_value" type="number" min="0" step="0.01" style="width:100px" />
        </label>
      </div>

      <!-- Active toggle -->
      <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.25rem">
        <label class="toggle" style="flex-direction:row;text-transform:none;font-size:0.875rem;font-weight:500;color:var(--text-1);gap:0;align-items:center">
          <input id="rule-active" type="checkbox" v-model="form.active" />
          <span class="toggle-track"></span>
        </label>
        <span style="font-size:0.875rem;color:var(--text-2)">
          Rule is <strong :style="form.active ? 'color:var(--success)' : 'color:var(--text-3)'">
            {{ form.active ? 'active' : 'inactive' }}
          </strong>
        </span>
      </div>

      <!-- Condition builder -->
      <div class="form-section-title" style="margin-bottom:0.75rem">
        IF conditions (all must match)
      </div>
      <div class="condition-builder">
        <div v-for="(cond, idx) in form.conditions" :key="idx" class="condition-row">
          <select :id="`cond-key-${idx}`" v-model="cond.key" style="max-width:180px">
            <option v-for="k in conditionKeys" :key="k.value" :value="k.value">{{ k.label }}</option>
          </select>

          <!-- Value input depends on key type -->
          <select v-if="cond.key === 'customer_type'" :id="`cond-val-${idx}`" v-model="cond.value">
            <option v-for="o in customerTypeOptions" :key="o" :value="o">{{ o }}</option>
          </select>
          <select v-else-if="cond.key === 'season'" :id="`cond-val-${idx}`" v-model="cond.value">
            <option v-for="o in seasonOptions" :key="o" :value="o">{{ o }}</option>
          </select>
          <input v-else :id="`cond-val-${idx}`" v-model="cond.value" type="number" min="1" placeholder="value" />

          <button class="remove-btn danger" @click="removeCondition(idx)" title="Remove condition">✕</button>
        </div>
        <button @click="addCondition" style="align-self:flex-start;font-size:0.8rem;margin-top:0.25rem">
          + Add condition
        </button>
      </div>

      <div class="hint" style="margin-top:1rem">
        <strong>Condition keys:</strong>
        <code>quantity_gte</code>, <code>quantity_gt</code>, <code>quantity_lte</code>,
        <code>customer_type</code> (standard/corporate/wholesale),
        <code>season</code> (normal/peak/off_peak)
      </div>
    </div>

    <!-- Rules table -->
    <div class="card fade-in" style="animation-delay:0.1s">
      <div class="card-header">
        <div>
          <h2>Active Rule Set</h2>
          <p style="font-size:0.85rem;color:var(--text-2);margin-top:0.2rem">
            {{ rules.length }} rule{{ rules.length !== 1 ? 's' : '' }} configured
          </p>
        </div>
        <a href="/" style="font-size:0.85rem;color:var(--accent)">→ Test in Pricing Demo</a>
      </div>

      <div v-if="rules.length" class="table-wrap">
        <table id="rules-table">
          <thead>
            <tr>
              <th>Priority</th>
              <th>Rule name</th>
              <th>Conditions (IF)</th>
              <th>Action (THEN)</th>
              <th>Value</th>
              <th>Status</th>
              <th style="text-align:right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in rules" :key="r.id">
              <td><span class="priority-badge">{{ r.priority }}</span></td>
              <td style="font-weight:600">{{ r.name }}</td>
              <td>
                <span v-for="tag in formatConditions(r.conditions)" :key="tag" class="condition-tag">
                  {{ tag }}
                </span>
              </td>
              <td><span :class="actionClass(r.action_type)">{{ actionLabel(r.action_type) }}</span></td>
              <td style="font-weight:600;font-variant-numeric:tabular-nums">{{ parseFloat(r.action_value).toFixed(2) }}</td>
              <td>
                <span :class="r.active ? 'status-badge status-active' : 'status-badge status-inactive'">
                  {{ r.active ? '● Active' : '○ Inactive' }}
                </span>
              </td>
              <td>
                <div class="td-actions">
                  <button class="btn-sm" @click="toggleActive(r)" :title="r.active ? 'Deactivate' : 'Activate'">
                    {{ r.active ? 'Pause' : 'Enable' }}
                  </button>
                  <button class="btn-sm" @click="edit(r)">Edit</button>
                  <button class="btn-sm danger" @click="remove(r.id)">Delete</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="empty-state">
        <div class="empty-icon">📋</div>
        <p>No pricing rules yet. Create one above to get started.</p>
      </div>

      <div class="hint">
        Rules are evaluated highest-priority first. Multiple rules can apply to the same request.
        Use <code>fixed_unit_price</code> to override all previous calculations.
      </div>
    </div>
  </AppLayout>
</template>
