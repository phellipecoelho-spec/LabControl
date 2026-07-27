<template>
  <div class="empty-state">
    <div class="empty-state__content">
      <i class="pi pi-chart-bar empty-state__icon"></i>
      <h3>Bem-vindo ao LabControl</h3>
      <p>Seu dashboard ainda não possui dados. Comece cadastrando equipamentos, calibrações e movimentações.</p>
      <div class="empty-state__actions">
        <Button
          label="Cadastrar Equipamento"
          icon="pi pi-plus"
          @click="$router.push({ name: 'equipment-create' })"
        />
        <Button
          label="Ver Calibrações"
          icon="pi pi-calendar"
          severity="secondary"
          variant="text"
          @click="$router.push({ name: 'calibrations.index' })"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import Button from 'primevue/button'
</script>

<style scoped>
.empty-state {
  display: flex;
  justify-content: center;
  padding: 4rem 1.5rem;
}

.empty-state__content {
  text-align: center;
  max-width: 480px;
}

.empty-state__icon {
  font-size: 3rem;
  color: var(--p-primary-color);
  margin-bottom: 1rem;
}

.empty-state__content h3 {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0 0 0.5rem;
  color: var(--app-text-primary, #e2e8f0);
}

.empty-state__content p {
  font-size: 0.875rem;
  color: var(--app-text-secondary, #94a3b8);
  margin: 0 0 1.5rem;
  line-height: 1.5;
}

.empty-state__actions {
  display: flex;
  gap: 0.75rem;
  justify-content: center;
  flex-wrap: wrap;
}
</style>
