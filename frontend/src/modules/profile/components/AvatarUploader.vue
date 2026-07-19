<template>
  <div class="avatar-uploader">
    <div class="flex align-items-center gap-4 mb-4">
      <Avatar
        v-if="avatarUrl"
        :image="avatarUrl"
        size="xlarge"
        shape="circle"
      />
      <Avatar
        v-else
        :label="avatarInitials"
        size="xlarge"
        shape="circle"
      />
      <div>
        <p class="font-bold text-lg">{{ auth.user?.name }}</p>
        <p class="text-sm text-muted-color">{{ auth.user?.email }}</p>
      </div>
    </div>

    <div class="mb-3">
      <FileUpload
        mode="basic"
        accept="image/*"
        :maxFileSize="2097152"
        :customUpload="true"
        chooseLabel="Alterar Avatar"
        @uploader="onAvatarSelect"
      />
    </div>

    <Button
      v-if="auth.user?.avatar_path"
      label="Remover Avatar"
      severity="danger"
      icon="pi pi-trash"
      text
      :loading="removing"
      @click="removeAvatar"
    />

    <div v-if="uploadError" class="mt-3">
      <Message severity="error">{{ uploadError }}</Message>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import FileUpload from 'primevue/fileupload'
import Avatar from 'primevue/avatar'
import Button from 'primevue/button'
import Message from 'primevue/message'
import { useAuthStore } from '@/stores/auth'
import { api } from '@/services/api'

const auth = useAuthStore()
const toast = useToast()
const uploadError = ref('')
const removing = ref(false)

const avatarUrl = computed(() => {
  return auth.user?.avatar_path ? `/storage/${auth.user.avatar_path}` : null
})

const avatarInitials = computed(() => {
  return auth.user?.name?.charAt(0)?.toUpperCase() || '?'
})

async function onAvatarSelect(event: any) {
  uploadError.value = ''

  const file = event.files?.[0]
  if (!file) return

  const formData = new FormData()
  formData.append('avatar', file)

  try {
    await api.post('/profile/avatar', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    await auth.fetchUser()
    toast.add({ severity: 'success', summary: 'Sucesso', detail: 'Avatar atualizado com sucesso.', life: 3000 })
  } catch (e: any) {
    if (e.response?.status === 422) {
      uploadError.value = e.response.data.message || 'Arquivo inválido. Verifique o formato e tamanho.'
    } else {
      toast.add({ severity: 'error', summary: 'Erro', detail: e.response?.data?.message || 'Erro ao enviar avatar.', life: 5000 })
    }
  }
}

async function removeAvatar() {
  removing.value = true

  try {
    await api.delete('/profile/avatar')
    await auth.fetchUser()
    toast.add({ severity: 'success', summary: 'Sucesso', detail: 'Avatar removido com sucesso.', life: 3000 })
  } catch (e: any) {
    toast.add({ severity: 'error', summary: 'Erro', detail: e.response?.data?.message || 'Erro ao remover avatar.', life: 5000 })
  } finally {
    removing.value = false
  }
}
</script>
