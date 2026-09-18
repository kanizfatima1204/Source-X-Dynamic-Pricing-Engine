<template>
  <div class="shell">
    <header>
      <div class="header-brand">
        <div class="logo-icon">SX</div>
        <strong>Source X</strong>
      </div>
      <nav>
        <a href="/" :class="{ active: isActive('/') }">⚡ Pricing Demo</a>
        <a href="/admin/rules" :class="{ active: isActive('/admin/rules') }">⚙ Admin Rules</a>
        <a href="/admin/products" :class="{ active: isActive('/admin/products') }">📦 Products</a>
      </nav>
    </header>
    <main>
      <slot />
    </main>
  </div>
</template>

<script setup>
const isActive = (path) => window.location.pathname === path;
</script>
