<script setup>
import { RouterView } from 'vue-router'
</script>

<template>
  <header class="app-header">
    <strong>Fênix</strong>
  </header>
  <main class="app-main">
    <RouterView />
  </main>
</template>

<style>
:root {
  --color-bg: #f6f7f9;
  --color-surface: #ffffff;
  --color-text: #1f2933;
  --color-muted: #616e7c;
  --color-border: #d9e2ec;
  --color-primary: #e8590c;
  --color-success: #2f9e44;
  --color-danger: #e03131;
}

* {
  box-sizing: border-box;
}

body {
  margin: 0;
  font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
  background: var(--color-bg);
  color: var(--color-text);
}

.app-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem 1.5rem;
  background: var(--color-surface);
  border-bottom: 1px solid var(--color-border);
}

.app-main {
  max-width: 1080px;
  margin: 0 auto;
  padding: 1.5rem 1rem;
}
</style>
