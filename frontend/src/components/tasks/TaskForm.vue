<script setup lang="ts">
import type { TaskI, TaskErroresFormI } from '@/interfaces/TaskI';
import type { TaskStatus, TaskPriority } from '@/interfaces/enums';

defineProps<{
  form: TaskI;
  errores: TaskErroresFormI;
}>();

const statuses: { title: string; value: TaskStatus }[] = [
  { title: 'Pendiente',    value: 'pending' },
  { title: 'En progreso',  value: 'in_progress' },
  { title: 'Revisión',     value: 'review' },
  { title: 'Completada',   value: 'done' },
  { title: 'Bloqueada',    value: 'blocked' },
];

const priorities: { title: string; value: TaskPriority }[] = [
  { title: 'Baja',     value: 'low' },
  { title: 'Media',    value: 'medium' },
  { title: 'Alta',     value: 'high' },
  { title: 'Crítica',  value: 'critical' },
];
</script>

<template>
  <VCard class="mb-4">
    <VCardItem>
      <h5 class="text-h5 text-wrap">Datos de la tarea</h5>
    </VCardItem>

    <VCardText class="px-8 pb-8">
      <VRow>
        <VCol cols="12">
          <VTextField v-model="form.title" :error-messages="errores.title"
            name="title" label="Título" placeholder="Título de la tarea" />
        </VCol>

        <VCol cols="12">
          <VTextarea v-model="form.description" :error-messages="errores.description"
            name="description" label="Descripción" placeholder="Descripción de la tarea" rows="3" />
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
          <VTextField v-model="form.due_date" :error-messages="errores.due_date"
            name="due_date" type="date" label="Fecha límite" />
        </VCol>

        <VCol cols="12" md="4">
          <VTextField v-model="form.estimated_hours" :error-messages="errores.estimated_hours"
            name="estimated_hours" type="number" label="Horas estimadas" placeholder="0" />
        </VCol>

        <VCol cols="12" md="4">
          <VTextField v-model="form.progress" :error-messages="errores.progress"
            name="progress" type="number" label="Progreso (%)" placeholder="0" />
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
