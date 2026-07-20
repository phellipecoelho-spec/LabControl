<template>
  <div class="dashboard">
    <h1>LabControl</h1>
    <p>Plataforma de Gestão Laboratorial</p>
  </div>
</template>

<script setup lang="ts">
</script>

<style scoped>
.dashboard {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100vh;
  gap: 1rem;
  color: var(--app-text-primary);
}

.dashboard h1 {
  font-size: 2rem;
  font-weight: 700;
}

.dashboard p {
  color: var(--app-text-secondary);
  font-size: 1.125rem;
}
</style>
