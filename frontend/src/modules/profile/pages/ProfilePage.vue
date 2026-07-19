<template>
  <div class="profile-page">
    <div class="page-header mb-4">
      <h1 class="text-3xl font-bold">Meu Perfil</h1>
      <p class="text-muted-color text-sm">Gerencie suas informações pessoais</p>
    </div>

    <TabView v-model:activeIndex="activeIndex">
      <TabPanel header="Informações">
        <ProfileInfoForm @saved="onInfoSaved" />
      </TabPanel>
      <TabPanel header="Senha">
        <PasswordChangeForm />
      </TabPanel>
      <TabPanel header="Avatar">
        <AvatarUploader />
      </TabPanel>
    </TabView>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import { useAuthStore } from '@/stores/auth'
import ProfileInfoForm from '@/modules/profile/components/ProfileInfoForm.vue'
import PasswordChangeForm from '@/modules/profile/components/PasswordChangeForm.vue'
import AvatarUploader from '@/modules/profile/components/AvatarUploader.vue'

const auth = useAuthStore()
const activeIndex = ref(0)

function onInfoSaved() {
  activeIndex.value = 0
}

onMounted(async () => {
  await auth.fetchUser()
})
</script>
