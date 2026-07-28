<template>
  <Dialog
    :visible="visible"
    header="Conflito de Sincronização"
    modal
    :closable="true"
    :style="{ width: '600px' }"
    @update:visible="$emit('update:visible', $event as boolean)"
  >
    <div v-if="conflict" class="conflict-dialog">
      <div class="conflict-dialog__header">
        <span class="conflict-dialog__entity">
          <strong>Entidade:</strong> {{ conflict.entityType }}
        </span>
        <span class="conflict-dialog__entity">
          <strong>ID:</strong> {{ conflict.entityId }}
        </span>
      </div>

      <p v-if="diffFields.length === 0" class="conflict-dialog__no-data">
        Não foi possível carregar os detalhes do conflito.
      </p>

      <div v-else class="conflict-dialog__diff">
        <div
          v-for="field in diffFields"
          :key="field.name"
          class="conflict-dialog__row"
        >
          <div class="conflict-dialog__field-name">{{ field.name }}</div>
          <div class="conflict-dialog__value conflict-dialog__value--local">
            <span class="conflict-dialog__value-label">Local</span>
            <span class="conflict-dialog__value-text">{{ formatValue(field.local) }}</span>
          </div>
          <div class="conflict-dialog__value conflict-dialog__value--server">
            <span class="conflict-dialog__value-label">Servidor</span>
            <span class="conflict-dialog__value-text">{{ formatValue(field.server) }}</span>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="conflict-dialog__empty">
      <p>Nenhum conflito selecionado.</p>
    </div>

    <template #footer>
      <div class="conflict-dialog__footer">
        <Button
          label="Manter Local"
          severity="warn"
          icon="pi pi-save"
          @click="emitResolve('keep-local')"
        />
        <Button
          label="Manter Servidor"
          severity="info"
          icon="pi pi-cloud"
          @click="emitResolve('keep-server')"
        />
        <Button
          label="Fechar"
          severity="secondary"
          variant="text"
          @click="$emit('update:visible', false)"
        />
      </div>
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import type { ConflictRecord } from '@/db'

const props = defineProps<{
  visible: boolean
  conflict: ConflictRecord | null
}>()

const emit = defineEmits<{
  resolve: [payload: { conflictId: number; resolution: 'keep-local' | 'keep-server' }]
  'update:visible': [value: boolean]
}>()

interface DiffField {
  name: string
  local: unknown
  server: unknown
}

const diffFields = computed<DiffField[]>(() => {
  if (!props.conflict) return []
  const allKeys = new Set([
    ...Object.keys(props.conflict.localVersion),
    ...Object.keys(props.conflict.serverVersion),
  ])
  const fields: DiffField[] = []
  for (const key of allKeys) {
    const localVal = key in props.conflict.localVersion ? props.conflict.localVersion[key] : undefined
    const serverVal = key in props.conflict.serverVersion ? props.conflict.serverVersion[key] : undefined
    if (JSON.stringify(localVal) !== JSON.stringify(serverVal)) {
      fields.push({ name: key, local: localVal, server: serverVal })
    }
  }
  return fields
})

function formatValue(val: unknown): string {
  if (val === null || val === undefined) return '(vazio)'
  if (typeof val === 'object') return JSON.stringify(val, null, 2)
  return String(val)
}

function emitResolve(resolution: 'keep-local' | 'keep-server'): void {
  if (!props.conflict?.id) return
  emit('resolve', { conflictId: props.conflict.id, resolution })
}
</script>

<style scoped>
.conflict-dialog__header {
  display: flex;
  gap: 1.5rem;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--p-content-border-color);
}

.conflict-dialog__entity {
  font-size: 0.875rem;
}

.conflict-dialog__no-data,
.conflict-dialog__empty {
  text-align: center;
  color: var(--p-text-muted-color);
  padding: 2rem 0;
}

.conflict-dialog__diff {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  max-height: 400px;
  overflow-y: auto;
}

.conflict-dialog__row {
  display: grid;
  grid-template-columns: 120px 1fr 1fr;
  gap: 0.5rem;
  align-items: start;
  padding: 0.5rem;
  border-radius: 6px;
  border: 1px solid var(--p-content-border-color);
}

.conflict-dialog__field-name {
  font-weight: 600;
  font-size: 0.875rem;
  padding-top: 0.25rem;
  word-break: break-all;
}

.conflict-dialog__value {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  padding: 0.375rem;
  border-radius: 4px;
  min-height: 2rem;
}

.conflict-dialog__value--local {
  background-color: #fef3c7; /* amber-100 */
}

.conflict-dialog__value--server {
  background-color: #dbeafe; /* blue-100 */
}

.conflict-dialog__value-label {
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  opacity: 0.7;
}

.conflict-dialog__value-text {
  font-size: 0.8125rem;
  word-break: break-all;
  white-space: pre-wrap;
}

.conflict-dialog__footer {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
}
</style>
