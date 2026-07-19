<template>
  <div class="role-permission-editor">
    <div v-if="!role" class="flex flex-column align-items-center justify-content-center h-full text-center p-5">
      <i class="pi pi-shield text-6xl text-300 mb-3"></i>
      <p class="text-lg text-500">Selecione um perfil para gerenciar as permissões</p>
    </div>

    <div v-else-if="isAdminRole" class="flex flex-column align-items-center justify-content-center h-full text-center p-5">
      <i class="pi pi-lock text-6xl text-500 mb-3"></i>
      <p class="text-lg text-600">O perfil de Administrador possui todas as permissões do sistema</p>
      <p class="text-sm text-400">Essa configuração não pode ser alterada.</p>
    </div>

    <div v-else>
      <div class="flex align-items-center justify-content-between mb-3">
        <h3 class="m-0">{{ role.name }}</h3>
        <Button label="Salvar Permissões" icon="pi pi-check" @click="onSave" />
      </div>

      <Accordion :value="expandedGroups" multiple>
        <AccordionPanel v-for="group in groupedPermissions" :key="group.group" :value="group.group">
          <AccordionHeader>
            <div class="flex align-items-center gap-2">
              <span class="font-medium">{{ group.group }}</span>
              <Tag :value="`${countEnabled(group)}/${group.permissions.length}`" severity="info" rounded />
            </div>
          </AccordionHeader>
          <AccordionContent>
            <div class="flex flex-column gap-2 p-2">
              <div
                v-for="permission in group.permissions"
                :key="permission.id"
                class="flex align-items-center justify-content-between p-2 border-round hover:surface-hover"
              >
                <div>
                  <span class="font-medium">{{ permission.name }}</span>
                  <span v-if="permission.description" class="block text-xs text-500">{{ permission.description }}</span>
                </div>
                <ToggleSwitch v-model="selectedPermissions[permission.id]" @change="onToggle" />
              </div>
            </div>
          </AccordionContent>
        </AccordionPanel>
      </Accordion>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Accordion from 'primevue/accordion'
import AccordionPanel from 'primevue/accordionpanel'
import AccordionHeader from 'primevue/accordionheader'
import AccordionContent from 'primevue/accordioncontent'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import ToggleSwitch from 'primevue/toggleswitch'
import type { Role, Permission } from '@/stores/roles'

const props = defineProps<{
  role: Role | null
  allPermissions: Permission[]
}>()

const emit = defineEmits<{
  save: [roleId: string, permissionIds: string[]]
}>()

const isAdminRole = computed(() => props.role?.slug === 'admin')

const selectedPermissions = ref<Record<string, boolean>>({})

const expandedGroups = ref<string[]>([])

const groupedPermissions = computed(() => {
  const groups = new Map<string, Permission[]>()
  for (const perm of props.allPermissions) {
    const group = perm.group || 'Geral'
    if (!groups.has(group)) {
      groups.set(group, [])
    }
    groups.get(group)!.push(perm)
  }
  return Array.from(groups.entries()).map(([group, permissions]) => ({
    group,
    permissions,
  }))
})

function countEnabled(group: { permissions: Permission[] }): number {
  return group.permissions.filter(p => selectedPermissions.value[p.id]).length
}

function onToggle() {
  // reactivity is automatic via v-model
}

watch(() => props.role, (role) => {
  if (role && !isAdminRole.value) {
    const perms: Record<string, boolean> = {}
    for (const perm of props.allPermissions) {
      perms[perm.id] = role.permissions?.some(p => p.id === perm.id) ?? false
    }
    selectedPermissions.value = perms
    expandedGroups.value = Object.keys(perms).length > 0
      ? groupedPermissions.value.map(g => g.group)
      : []
  }
}, { immediate: true })

function onSave() {
  if (!props.role) return
  const permissionIds = Object.entries(selectedPermissions.value)
    .filter(([, selected]) => selected)
    .map(([id]) => id)
  emit('save', props.role.id, permissionIds)
}
</script>

<style scoped>
.role-permission-editor {
  min-height: 400px;
}
</style>
