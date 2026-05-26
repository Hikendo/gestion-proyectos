<script setup lang="ts">
import type { ProjectPhaseI, ProjectPhaseErroresFormI } from '@/interfaces/ProjectPhaseI';

defineProps<{
  form: ProjectPhaseI;
  errores: ProjectPhaseErroresFormI;
}>();
</script>

<template>
  <VCard class="mb-4">
    <VCardItem>
      <h5 class="text-h5 text-wrap">Datos de la fase</h5>
    </VCardItem>

    <VCardText class="px-8 pb-8">
      <VRow>
        <VCol cols="12">
          <VTextField v-model="form.name" :error-messages="errores.name"
            name="name" label="Nombre" placeholder="Nombre de la fase" />
        </VCol>

        <VCol cols="12" md="4">
          <VTextField v-model="form.start_date" :error-messages="errores.start_date"
            name="start_date" type="date" label="Fecha de inicio" />
        </VCol>

        <VCol cols="12" md="4">
          <VTextField v-model="form.end_date" :error-messages="errores.end_date"
            name="end_date" type="date" label="Fecha de fin" />
        </VCol>

        <VCol cols="12" md="4">
          <VTextField v-model="form.progress" :error-messages="errores.progress"
            name="progress" type="number" label="Progreso (%)" placeholder="0" />
        </VCol>

        <VCol cols="12" class="d-flex gap-4">
          <VSpacer />
          <VBtn type="submit">Guardar</VBtn>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>
