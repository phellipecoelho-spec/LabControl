<template>
  <div class="loading-skeleton">
    <!-- Table variant -->
    <template v-if="variant === 'table'">
      <div class="loading-skeleton__toolbar">
        <Skeleton width="200px" height="2.5rem" />
      </div>
      <div class="loading-skeleton__table">
        <div class="loading-skeleton__table-header">
          <Skeleton v-for="col in columnCount" :key="'h-' + col" :width="colWidth(col)" height="1.5rem" />
        </div>
        <div v-for="row in rows" :key="'r-' + row" class="loading-skeleton__table-row">
          <Skeleton v-for="col in columnCount" :key="'c-' + row + '-' + col" :width="colWidth(col)" height="1.25rem" />
        </div>
      </div>
    </template>

    <!-- Card variant -->
    <template v-if="variant === 'card'">
      <div class="loading-skeleton__card-grid">
        <div v-for="n in rows" :key="n" class="loading-skeleton__card">
          <Skeleton width="100%" height="160px" class="mb-3" borderRadius="12px" />
          <Skeleton width="70%" height="1.25rem" class="mb-2" />
          <Skeleton width="50%" height="1rem" class="mb-3" />
          <Skeleton width="100%" height="2.5rem" />
        </div>
      </div>
    </template>

    <!-- Form variant -->
    <template v-if="variant === 'form'">
      <div class="loading-skeleton__form">
        <div v-for="n in rows" :key="n" class="loading-skeleton__form-field">
          <Skeleton width="120px" height="0.875rem" class="mb-2" />
          <Skeleton width="100%" height="2.75rem" />
        </div>
        <div class="loading-skeleton__form-actions">
          <Skeleton width="120px" height="2.5rem" />
          <Skeleton width="120px" height="2.5rem" />
        </div>
      </div>
    </template>

    <!-- Detail variant -->
    <template v-if="variant === 'detail'">
      <div class="loading-skeleton__detail">
        <div class="loading-skeleton__detail-header">
          <Skeleton width="250px" height="1.75rem" class="mb-2" />
          <Skeleton width="180px" height="1.25rem" />
        </div>
        <div class="loading-skeleton__detail-body">
          <div v-for="n in rows" :key="n" class="loading-skeleton__detail-row">
            <div class="loading-skeleton__detail-label">
              <Skeleton width="100%" height="1rem" />
            </div>
            <div class="loading-skeleton__detail-value">
              <Skeleton width="100%" height="1.25rem" />
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import Skeleton from 'primevue/skeleton'

const props = withDefaults(defineProps<{
  variant: 'table' | 'card' | 'form' | 'detail'
  rows?: number
}>(), {
  rows: 5,
})

const columnCount = 6

function colWidth(col: number): string {
  const widths = ['20%', '15%', '15%', '15%', '15%', '20%']
  return widths[(col - 1) % widths.length]
}
</script>

<style scoped>
.loading-skeleton {
  padding: 1rem 0;
}

/* Table variant */
.loading-skeleton__toolbar {
  margin-bottom: 1rem;
}

.loading-skeleton__table {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.loading-skeleton__table-header {
  display: flex;
  gap: 1rem;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--p-surface-200);
}

.loading-skeleton__table-row {
  display: flex;
  gap: 1rem;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--p-surface-100);
}

/* Card variant */
.loading-skeleton__card-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1rem;
}

.loading-skeleton__card {
  padding: 1rem;
  border-radius: 12px;
  background: var(--p-surface-card);
}

/* Form variant */
.loading-skeleton__form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  max-width: 600px;
}

.loading-skeleton__form-field {
  display: flex;
  flex-direction: column;
}

.loading-skeleton__form-actions {
  display: flex;
  gap: 0.75rem;
  margin-top: 0.5rem;
}

/* Detail variant */
.loading-skeleton__detail {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.loading-skeleton__detail-header {
  margin-bottom: 0.5rem;
}

.loading-skeleton__detail-body {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.loading-skeleton__detail-row {
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: 1rem;
  align-items: center;
}

.loading-skeleton__detail-label {
  text-align: right;
}

.loading-skeleton__detail-value {
  min-width: 0;
}

@media (max-width: 768px) {
  .loading-skeleton__detail-row {
    grid-template-columns: 1fr;
    gap: 0.25rem;
  }

  .loading-skeleton__detail-label {
    text-align: left;
  }

  .loading-skeleton__table-header,
  .loading-skeleton__table-row {
    gap: 0.5rem;
    padding: 0.5rem;
  }
}
</style>