<template>
  <div class="empty-state">
    <div class="empty-state__container">
      <i :class="icon" class="empty-state__icon"></i>
      <h3 class="empty-state__title">{{ title }}</h3>
      <p v-if="description" class="empty-state__description">{{ description }}</p>
      <div v-if="$slots.actions || actionLabel" class="empty-state__actions">
        <slot name="actions">
          <Button
            v-if="actionLabel"
            :label="actionLabel"
            :icon="actionIcon"
            @click="handleAction"
          />
        </slot>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import Button from 'primevue/button'

const props = withDefaults(defineProps<{
  icon?: string
  title: string
  description?: string
  actionLabel?: string
  actionRoute?: string
  actionIcon?: string
}>(), {
  icon: 'pi pi-inbox',
  actionIcon: 'pi pi-plus',
})

const router = useRouter()

function handleAction() {
  if (props.actionRoute) {
    router.push(props.actionRoute)
  }
}
</script>

<style scoped>
.empty-state {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 4rem 1.5rem;
  min-height: 300px;
}

.empty-state__container {
  text-align: center;
  max-width: 400px;
}

.empty-state__icon {
  font-size: 3.5rem;
  opacity: 0.35;
  color: var(--p-text-muted-color);
  margin-bottom: 1.25rem;
  display: inline-block;
}

.empty-state__title {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0 0 0.5rem;
  color: var(--p-text-color);
  line-height: 1.4;
}

.empty-state__description {
  font-size: 0.875rem;
  color: var(--p-text-muted-color);
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