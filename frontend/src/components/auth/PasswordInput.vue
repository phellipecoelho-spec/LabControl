<template>
  <div class="field">
    <label :for="inputId">{{ label }}</label>
    <div class="p-inputgroup">
      <InputText
        :id="inputId"
        :type="showPassword ? 'text' : 'password'"
        :modelValue="modelValue"
        @update:modelValue="$emit('update:modelValue', $event)"
        :placeholder="placeholder"
        :class="{ 'p-invalid': !!error }"
        class="w-full"
        :aria-label="label"
        :autocomplete="autocomplete"
      />
      <Button
        :icon="showPassword ? 'pi pi-eye-slash' : 'pi pi-eye'"
        severity="secondary"
        @click="showPassword = !showPassword"
        :aria-label="showPassword ? 'Esconder senha' : 'Mostrar senha'"
      />
    </div>
    <small v-if="error" class="p-error">{{ error }}</small>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'

const props = defineProps<{
  modelValue: string
  label: string
  placeholder?: string
  error?: string
  autocomplete?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const showPassword = ref(false)
const inputId = `password-${Math.random().toString(36).slice(2, 8)}`
</script>
