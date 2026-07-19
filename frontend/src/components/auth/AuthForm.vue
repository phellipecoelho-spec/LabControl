<template>
  <div class="auth-container">
    <Card>
      <template #title>
        <h2>{{ title }}</h2>
        <p v-if="description" class="text-sm text-muted-color">{{ description }}</p>
      </template>
      <template #content>
        <form @submit.prevent="onSubmit">
          <slot />

          <div v-if="formErrors && Object.keys(formErrors).length > 0" class="mb-3">
            <Message
              v-for="(msgs, field) in formErrors"
              :key="field"
              severity="error"
              :closable="false"
            >
              <div v-for="(msg, i) in msgs" :key="i">{{ msg }}</div>
            </Message>
          </div>

          <Button
            :label="submitLabel"
            type="submit"
            :loading="loading"
            class="w-full mt-3"
            size="large"
          />
        </form>
      </template>
      <template #footer>
        <div class="text-center mt-2">
          <slot name="footer" />
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import Card from 'primevue/card'
import Button from 'primevue/button'
import Message from 'primevue/message'

defineProps<{
  title: string
  description?: string
  submitLabel: string
  loading?: boolean
  formErrors?: Record<string, string[]> | null
}>()

const emit = defineEmits<{
  submit: []
}>()

function onSubmit() {
  emit('submit')
}
</script>

<style scoped>
.auth-container {
  max-width: 28rem;
  margin: 4rem auto;
  padding: 0 1rem;
}
</style>
