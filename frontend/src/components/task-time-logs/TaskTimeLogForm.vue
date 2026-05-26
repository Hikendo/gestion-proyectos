<script setup lang="ts">
import type { TaskTimeLogI, TaskTimeLogErroresFormI } from '@/interfaces/TaskTimeLogI';

defineProps<{
  form: TaskTimeLogI;
  errores: TaskTimeLogErroresFormI;
}>();
</script>

<template>
  <VCard class="mb-4">
    <VCardItem>
      <h5 class="text-h5 text-wrap">Registrar tiempo</h5>
    </VCardItem>

    <VCardText class="px-8 pb-8">
      <VRow>
        <VCol cols="12" md="4">
          <VTextField v-model="form.minutes" :error-messages="errores.minutes"
            name="minutes" type="number" label="Minutos trabajados" placeholder="60" />
        </VCol>

        <VCol cols="12" md="8">
          <VTextField v-model="form.description" :error-messages="errores.description"
            name="description" label="Descripción" placeholder="¿En qué trabajaste?" />
        </VCol>

        <VCol cols="12" class="d-flex gap-4">
          <VSpacer />
          <VBtn type="submit">Registrar</VBtn>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>
