<script setup lang="ts">
import type { ObjectiveI, ObjectiveErroresFormI } from '@/interfaces/ObjectiveI';
import type { ObjectiveType } from '@/interfaces/enums';

defineProps<{
  form: ObjectiveI;
  errores: ObjectiveErroresFormI;
}>();

const types: { title: string; value: ObjectiveType }[] = [
  { title: 'General',    value: 'general' },
  { title: 'Específico', value: 'specific' },
];
</script>

<template>
  <VCard class="mb-4">
    <VCardItem>
      <VCardTitle class="text-h6">Datos del objetivo</VCardTitle>
    </VCardItem>

    <VCardText class="px-8 pb-8">
      <VRow>
        <VCol cols="12" md="4">
          <VSelect v-model="form.type" :error-messages="errores.type"
            name="type" :items="types" item-title="title" item-value="value"
            label="Tipo" eager />
        </VCol>

        <VCol cols="12" md="8">
          <VTextField v-model="form.title" :error-messages="errores.title"
            name="title" label="Título" placeholder="Título del objetivo" />
        </VCol>

        <VCol cols="12">
          <VTextarea v-model="form.description" :error-messages="errores.description"
            name="description" label="Descripción" placeholder="Descripción del objetivo" rows="3" />
        </VCol>

        <VCol cols="12" md="6" class="d-flex align-center">
          <VSwitch v-model="form.completed" :error-messages="errores.completed"
            name="completed" label="Completado" color="success" />
        </VCol>

        <VCol cols="12" class="d-flex gap-4">
          <VSpacer />
          <VBtn type="submit" color="primary" variant="flat" size="large">Guardar</VBtn>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>
