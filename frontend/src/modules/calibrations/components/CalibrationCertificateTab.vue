<template>
  <div class="calibration-certificate-tab">
    <div class="flex justify-content-between align-items-center mb-3">
      <h4 class="text-base font-medium m-0">Certificados</h4>
      <Button
        v-if="authStore.hasPermission('calibracoes.edit')"
        label="Adicionar Certificado"
        icon="pi pi-plus"
        size="small"
        @click="openFileSelector"
        :disabled="uploading"
      />
      <input
        ref="fileInput"
        type="file"
        accept=".pdf,.jpg,.jpeg,.png,.webp"
        style="display: none"
        @change="onFileSelected"
      />
    </div>

    <DataTable
      :value="certificates"
      stripedRows
      size="small"
      :loading="uploading"
    >
      <Column header="Nome do Arquivo">
        <template #body="{ data }">
          <span class="font-medium">{{ data.filename }}</span>
        </template>
      </Column>
      <Column header="Tipo">
        <template #body="{ data }">
          <span class="text-sm">{{ getMimeLabel(data.mime_type) }}</span>
        </template>
      </Column>
      <Column header="Tamanho">
        <template #body="{ data }">
          <span>{{ formatFileSize(data.size_bytes) }}</span>
        </template>
      </Column>
      <Column header="Nº Certificado">
        <template #body="{ data }">
          <span>{{ data.certificate_number || '—' }}</span>
        </template>
      </Column>
      <Column header="Emissor">
        <template #body="{ data }">
          <span>{{ data.issuer || '—' }}</span>
        </template>
      </Column>
      <Column header="Data Emissão">
        <template #body="{ data }">
          <span>{{ data.issued_at ? formatDate(data.issued_at) : '—' }}</span>
        </template>
      </Column>
      <Column header="Ações" style="width: 120px">
        <template #body="{ data }">
          <div class="flex gap-1">
            <Button
              icon="pi pi-download"
              severity="info"
              text
              rounded
              size="small"
              @click="downloadCertificate(data)"
            />
            <Button
              v-if="authStore.hasPermission('calibracoes.edit')"
              icon="pi pi-trash"
              severity="danger"
              text
              rounded
              size="small"
              @click="confirmDelete(data)"
            />
          </div>
        </template>
      </Column>
    </DataTable>

    <div v-if="certificates.length === 0" class="text-center text-600 p-4">
      <i class="pi pi-file-pdf text-2xl mb-2 block text-400" />
      <p>Nenhum certificado anexado.</p>
    </div>

    <ConfirmDialog />
    <Toast />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import { calibrationService } from '../services/CalibrationService'
import type { CalibrationCertificate } from '../types/calibration'

const props = defineProps<{
  calibrationId: string
  certificates: CalibrationCertificate[]
}>()

const emit = defineEmits<{
  refresh: []
}>()

const authStore = useAuthStore()
const confirm = useConfirm()
const toast = useToast()
const fileInput = ref<HTMLInputElement | null>(null)
const uploading = ref(false)

function openFileSelector() {
  fileInput.value?.click()
}

function getMimeLabel(mimeType: string): string {
  const labels: Record<string, string> = {
    'application/pdf': 'PDF',
    'image/jpeg': 'JPEG',
    'image/png': 'PNG',
    'image/webp': 'WebP',
  }
  return labels[mimeType] || mimeType
}

function formatFileSize(bytes: number): string {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}

function formatDate(dateStr: string | null): string {
  if (!dateStr) return '—'
  try {
    const date = new Date(dateStr)
    return date.toLocaleDateString('pt-BR')
  } catch {
    return dateStr
  }
}

function downloadCertificate(certificate: CalibrationCertificate) {
  const url = `${import.meta.env.VITE_API_URL}/storage/${certificate.filepath}`
  window.open(url, '_blank')
}

function confirmDelete(certificate: CalibrationCertificate) {
  confirm.require({
    message: `Remover o certificado "${certificate.filename}"?`,
    header: 'Confirmar Exclusão',
    icon: 'pi pi-exclamation-triangle',
    rejectLabel: 'Cancelar',
    acceptLabel: 'Excluir',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await calibrationService.deleteCertificate(props.calibrationId, certificate.id)
        toast.add({
          severity: 'success',
          summary: 'Certificado removido',
          detail: 'O certificado foi removido com sucesso.',
          life: 3000,
        })
        emit('refresh')
      } catch (e: any) {
        toast.add({
          severity: 'error',
          summary: 'Erro ao remover certificado',
          detail: e.response?.data?.message || 'Ocorreu um erro ao processar a solicitação.',
          life: 5000,
        })
      }
    },
  })
}

async function onFileSelected(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input?.files?.[0]
  if (!file) return

  // Client-side validation
  const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp']
  const maxSize = 10 * 1024 * 1024 // 10MB

  if (!allowedTypes.includes(file.type)) {
    toast.add({
      severity: 'error',
      summary: 'Tipo de arquivo inválido',
      detail: 'Apenas PDF, JPG, PNG e WebP são permitidos.',
      life: 5000,
    })
    input.value = ''
    return
  }

  if (file.size > maxSize) {
    toast.add({
      severity: 'error',
      summary: 'Arquivo muito grande',
      detail: 'O tamanho máximo permitido é 10MB.',
      life: 5000,
    })
    input.value = ''
    return
  }

  uploading.value = true
  try {
    await calibrationService.uploadCertificate(props.calibrationId, file)
    toast.add({
      severity: 'success',
      summary: 'Certificado enviado',
      detail: 'O certificado foi anexado com sucesso.',
      life: 3000,
    })
    emit('refresh')
  } catch (e: any) {
    toast.add({
      severity: 'error',
      summary: 'Erro ao enviar certificado',
      detail: e.response?.data?.message || 'Ocorreu um erro ao fazer upload.',
      life: 5000,
    })
  } finally {
    uploading.value = false
    input.value = ''
  }
}
</script>
