<script setup lang="ts">
import type { TicketI, TicketErroresFormI } from '@/interfaces/TicketI';
import type { TicketStatus, TicketPriority } from '@/interfaces/enums';

defineProps<{
  form: TicketI;
  errores: TicketErroresFormI;
}>();

const statuses: { title: string; value: TicketStatus }[] = [
  { title: 'Abierto',      value: 'open' },
  { title: 'En progreso',  value: 'in_progress' },
  { title: 'Resuelto',     value: 'resolved' },
  { title: 'Cerrado',      value: 'closed' },
];

const priorities: { title: string; value: TicketPriority }[] = [
  { title: 'Baja',     value: 'low' },
  { title: 'Media',    value: 'medium' },
  { title: 'Alta',     value: 'high' },
  { title: 'Crítica',  value: 'critical' },
];
</script>

<template>
  <VCard class="mb-4">
    <VCardItem>
      <h5 class="text-h5 text-wrap">Datos del ticket</h5>
    </VCardItem>

    <VCardText class="px-8 pb-8">
      <VRow>
        <VCol cols="12">
          <VTextField v-model="form.subject" :error-messages="errores.subject"
            name="subject" label="Asunto" placeholder="Asunto del ticket" />
        </VCol>

        <VCol cols="12">
          <VTextarea v-model="form.description" :error-messages="errores.description"
            name="description" label="Descripción" placeholder="Descripción detallada" rows="4" />
        </VCol>

        <VCol cols="12" md="4">
          <VSelect v-model="form.status" :error-messages="errores.status"
            name="status" :items="statuses" item-title="title" item-value="value"
            label="Estado" eager />
        </VCol>

        <VCol cols="12" md="4">
          <VSelect v-model="form.priority" :error-messages="errores.priority"
            name="priority" :items="priorities" item-title="title" item-value="value"
            label="Prioridad" eager />
        </VCol>

        <VCol cols="12" md="4">
          <VTextField v-model="form.assigned_to" :error-messages="errores.assigned_to"
            name="assigned_to" type="number" label="Asignado a (ID)" placeholder="ID del usuario" />
        </VCol>

        <VCol cols="12" class="d-flex gap-4">
          <VSpacer />
          <VBtn type="submit">Guardar</VBtn>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>
