<template>
  <div class="equipment-photo-uploader">
    <div v-if="photos.length >= 10" class="text-warning mb-3">
      <i class="pi pi-exclamation-triangle mr-2"></i>
      Limite de 10 fotos atingido
    </div>

    <FileUpload
      mode="advanced"
      accept="image/jpeg,image/png,image/webp"
      :maxFileSize="5242880"
      :chooseLabel="photos.length < 10 ? 'Selecionar Fotos' : undefined"
      :disabled="photos.length >= 10"
      :customUpload="true"
      :showUploadButton="false"
      @select="onFileSelect"
    >
      <template #empty>
        <div class="text-center py-5">
          <i class="pi pi-cloud-upload text-4xl text-400 mb-3"></i>
          <p class="text-600">Arraste fotos aqui ou clique para selecionar</p>
          <p class="text-500 text-sm">JPG, PNG ou WebP — Máx 5MB</p>
        </div>
      </template>
    </FileUpload>

    <div v-if="photos.length > 0" class="photo-grid mt-4">
      <div
        v-for="(photo, index) in photos"
        :key="photo.id"
        class="photo-item"
      >
        <Image :src="getPhotoUrl(photo)" alt="Foto" width="100%" preview />
        <div class="photo-actions flex justify-content-between align-items-center mt-2">
          <div class="flex gap-1">
            <Button
              icon="pi pi-chevron-up"
              text
              rounded
              severity="secondary"
              size="small"
              :disabled="index === 0"
              @click="moveUp(index)"
            />
            <Button
              icon="pi pi-chevron-down"
              text
              rounded
              severity="secondary"
              size="small"
              :disabled="index === photos.length - 1"
              @click="moveDown(index)"
            />
          </div>
          <Button
            icon="pi pi-trash"
            text
            rounded
            severity="danger"
            size="small"
            @click="confirmDelete(photo)"
          />
        </div>
      </div>
    </div>

    <ConfirmDialog />
    <Toast />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import type { EquipmentPhoto } from '../types/equipment'
import { api } from '@/services/api'
import FileUpload from 'primevue/fileupload'
import Image from 'primevue/image'
import Button from 'primevue/button'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'

const props = defineProps<{
  equipmentId: string
  photos: EquipmentPhoto[]
}>()

const emit = defineEmits<{
  'photos-updated': [photos: EquipmentPhoto[]]
}>()

const toast = useToast()
const confirm = useConfirm()

function getPhotoUrl(photo: EquipmentPhoto): string {
  return `${import.meta.env.VITE_API_URL}/storage/${photo.path}`
}

async function onFileSelect(event: { files: File[] }) {
  const formData = new FormData()
  formData.append('photo', event.files[0])

  try {
    const response = await api.post(`/equipments/${props.equipmentId}/photos`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    emit('photos-updated', [...props.photos, response.data])
    toast.add({ severity: 'success', summary: 'Foto adicionada', life: 3000 })
  } catch {
    toast.add({ severity: 'error', summary: 'Erro ao fazer upload', life: 3000 })
  }
}

async function deletePhoto(photo: EquipmentPhoto) {
  try {
    await api.delete(`/equipments/${props.equipmentId}/photos/${photo.id}`)
    emit('photos-updated', props.photos.filter(p => p.id !== photo.id))
    toast.add({ severity: 'success', summary: 'Foto removida', life: 3000 })
  } catch {
    toast.add({ severity: 'error', summary: 'Erro ao remover foto', life: 3000 })
  }
}

function confirmDelete(photo: EquipmentPhoto) {
  confirm.require({
    message: 'Remover esta foto?',
    header: 'Confirmar',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Sim',
    rejectLabel: 'Não',
    accept: () => deletePhoto(photo),
  })
}

async function reorder(photoIds: string[]) {
  try {
    const response = await api.post(`/equipments/${props.equipmentId}/photos/reorder`, {
      photo_ids: photoIds,
    })
    emit('photos-updated', response.data)
  } catch {
    toast.add({ severity: 'error', summary: 'Erro ao reordenar', life: 3000 })
  }
}

function moveUp(index: number) {
  const ids = [...props.photos.map(p => p.id)]
  ;[ids[index - 1], ids[index]] = [ids[index], ids[index - 1]]
  reorder(ids)
}

function moveDown(index: number) {
  const ids = [...props.photos.map(p => p.id)]
  ;[ids[index], ids[index + 1]] = [ids[index + 1], ids[index]]
  reorder(ids)
}
</script>

<style scoped>
.photo-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
}

.photo-item {
  width: calc(33.333% - 0.667rem);
  min-width: 200px;
  border: 1px solid var(--surface-border);
  border-radius: 8px;
  padding: 0.75rem;
  background: var(--surface-card);
}
</style>
