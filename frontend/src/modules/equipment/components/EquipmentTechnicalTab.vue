<template>
  <div class="equipment-technical-tab">
    <div class="grid">
      <div class="col-12">
        <div class="card">
          <h3 class="text-lg font-semibold mb-3">Descrição</h3>
          <p v-if="equipment.description" class="text-700 whitespace-pre-line">
            {{ equipment.description }}
          </p>
          <p v-else class="text-500 italic">
            Nenhuma descrição cadastrada.
          </p>
        </div>
      </div>
      <div class="col-12">
        <div class="card">
          <h3 class="text-lg font-semibold mb-3">Especificações Técnicas</h3>
          <p v-if="equipment.technical_specs" class="text-700 whitespace-pre-line">
            {{ equipment.technical_specs }}
          </p>
          <p v-else class="text-500 italic">
            Nenhuma especificação técnica cadastrada.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Equipment } from '../types/equipment'

defineProps<{
  equipment: Equipment
}>()
</script>