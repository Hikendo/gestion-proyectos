<script setup lang="ts">
import type { MilestoneI, MilestoneErroresFormI } from '@/interfaces/MilestoneI';

defineProps<{
  form: MilestoneI;
  errores: MilestoneErroresFormI;
}>();
</script>

<template>
  <VCard class="mb-4">
    <VCardItem>
      <h5 class="text-h5 text-wrap">Datos del hito</h5>
    </VCardItem>

    <VCardText class="px-8 pb-8">
      <VRow>
        <VCol cols="12" md="8">
          <VTextField v-model="form.title" :error-messages="errores.title"
            name="title" label="Título" placeholder="Título del hito" />
        </VCol>

        <VCol cols="12" md="4">
          <VTextField v-model="form.target_date" :error-messages="errores.target_date"
            name="target_date" type="date" label="Fecha objetivo" />
        </VCol>

        <VCol cols="12" md="6" class="d-flex align-center">
          <VSwitch v-model="form.completed" :error-messages="errores.completed"
            name="completed" label="Completado" color="success" />
        </VCol>

        <VCol cols="12" class="d-flex gap-4">
          <VSpacer />
          <VBtn type="submit">Guardar</VBtn>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>
