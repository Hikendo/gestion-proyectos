<script setup lang="ts">
import type { BlockerI, BlockerErroresFormI } from '@/interfaces/BlockerI';
import type { BlockerSeverity } from '@/interfaces/enums';

defineProps<{
  form: BlockerI;
  errores: BlockerErroresFormI;
}>();

const severities: { title: string; value: BlockerSeverity }[] = [
  { title: 'Baja',     value: 'low' },
  { title: 'Media',    value: 'medium' },
  { title: 'Alta',     value: 'high' },
  { title: 'Crítica',  value: 'critical' },
];
</script>

<template>
  <VCard class="mb-4">
    <VCardItem>
      <h5 class="text-h5 text-wrap">Datos del bloqueador</h5>
    </VCardItem>

    <VCardText class="px-8 pb-8">
      <VRow>
        <VCol cols="12" md="8">
          <VTextField v-model="form.title" :error-messages="errores.title"
            name="title" label="Título" placeholder="Título del bloqueador" />
        </VCol>

        <VCol cols="12" md="4">
          <VSelect v-model="form.severity" :error-messages="errores.severity"
            name="severity" :items="severities" item-title="title" item-value="value"
            label="Severidad" eager />
        </VCol>

        <VCol cols="12">
          <VTextarea v-model="form.description" :error-messages="errores.description"
            name="description" label="Descripción" placeholder="Descripción del bloqueador" rows="3" />
        </VCol>

        <VCol cols="12" md="6">
          <VTextField v-model="form.task_id" :error-messages="errores.task_id"
            name="task_id" type="number" label="Tarea (ID)" placeholder="ID de la tarea" />
        </VCol>

        <VCol cols="12" md="6" class="d-flex align-center">
          <VSwitch v-model="form.resolved" :error-messages="errores.resolved"
            name="resolved" label="Resuelto" color="success" />
        </VCol>

        <VCol cols="12" class="d-flex gap-4">
          <VSpacer />
          <VBtn type="submit">Guardar</VBtn>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>
