<template>
  <div class="profile-page">
    <div class="page-header mb-4">
      <h1 class="text-3xl font-bold">Meu Perfil</h1>
      <p class="text-muted-color text-sm">Gerencie suas informações pessoais</p>
    </div>

    <Tabs v-model:value="activeTab">
      <TabList>
        <Tab value="0">Informações</Tab>
        <Tab value="1">Senha</Tab>
        <Tab value="2">Avatar</Tab>
      </TabList>
      <TabPanels>
        <TabPanel value="0">
          <ProfileInfoForm @saved="onInfoSaved" />
        </TabPanel>
        <TabPanel value="1">
          <PasswordChangeForm />
        </TabPanel>
        <TabPanel value="2">
          <AvatarUploader />
        </TabPanel>
      </TabPanels>
    </Tabs>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'
import { useAuthStore } from '@/stores/auth'
import ProfileInfoForm from '@/modules/profile/components/ProfileInfoForm.vue'
import PasswordChangeForm from '@/modules/profile/components/PasswordChangeForm.vue'
import AvatarUploader from '@/modules/profile/components/AvatarUploader.vue'

const auth = useAuthStore()
const activeTab = ref('0')

function onInfoSaved() {
  activeTab.value = '0'
}

onMounted(async () => {
  await auth.fetchUser()
})
</script>
