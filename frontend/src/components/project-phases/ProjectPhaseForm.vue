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
      <VCardTitle class="text-h6">Datos de la fase</VCardTitle>
    </VCardItem>

    <VCardText class="px-8 pb-8">
      <VRow>
        <VCol cols="12">
          <VTextField v-model="form.name" :error-messages="errores.name" name="name" label="Nombre"
            placeholder="Nombre de la fase" />
        </VCol>

        <VCol cols="12" md="4">
          <VTextField v-model="form.start_date" :error-messages="errores.start_date" name="start_date" type="date"
            label="Fecha de inicio" />
        </VCol>

        <VCol cols="12" md="4">
          <VTextField v-model="form.end_date" :error-messages="errores.end_date" name="end_date" type="date"
            label="Fecha de fin" />
        </VCol>

        <VCol cols="12" md="4">
          <div class="text-caption text-medium-emphasis mb-1">Progreso (%)</div>
          <div class="d-flex align-center gap-2">
            <VProgressLinear :model-value="form.progress ?? 0" color="primary" height="10" rounded
              class="flex-grow-1" />
            <span class="text-body-2 font-weight-medium">{{ form.progress ?? 0 }}%</span>
          </div>
          <div class="text-caption text-disabled mt-1">Calculado automáticamente por el progreso de las tareas</div>
        </VCol>

        <VCol cols="12" class="d-flex gap-4">
          <VSpacer />
          <VBtn type="submit" color="primary" variant="flat" size="large">Guardar</VBtn>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>
