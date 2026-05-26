<script setup lang="ts">
import type { ProjectPlanI, ProjectPlanErroresFormI } from '@/interfaces/ProjectPlanI';

defineProps<{
  form: ProjectPlanI;
  errores: ProjectPlanErroresFormI;
}>();
</script>

<template>
  <VCard class="mb-4">
    <VCardItem>
      <h5 class="text-h5 text-wrap">Plan del proyecto</h5>
    </VCardItem>

    <VCardText class="px-8 pb-8">
      <VRow>
        <VCol cols="12">
          <VTextarea v-model="form.scope" :error-messages="errores.scope"
            name="scope" label="Alcance" placeholder="Describe el alcance del proyecto" rows="4" />
        </VCol>

        <VCol cols="12">
          <VTextarea v-model="form.requirements" :error-messages="errores.requirements"
            name="requirements" label="Requerimientos" placeholder="Lista los requerimientos del proyecto" rows="4" />
        </VCol>

        <VCol cols="12">
          <VTextarea v-model="form.technical_notes" :error-messages="errores.technical_notes"
            name="technical_notes" label="Notas técnicas" placeholder="Notas y consideraciones técnicas" rows="4" />
        </VCol>

        <VCol cols="12" class="d-flex gap-4">
          <VSpacer />
          <VBtn type="submit">Guardar</VBtn>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>
