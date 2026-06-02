<script setup lang="ts">
import { ref, onMounted } from 'vue';
import type { BlockerI, BlockerErroresFormI } from '@/interfaces/BlockerI';
import type { BlockerSeverity } from '@/interfaces/enums';
import * as tasksService from '@/services/project-tasks.service';

const props = defineProps<{
  form: BlockerI;
  errores: BlockerErroresFormI;
  projectId: number;
}>();

const severities: { title: string; value: BlockerSeverity }[] = [
  { title: 'Baja',     value: 'low' },
  { title: 'Media',    value: 'medium' },
  { title: 'Alta',     value: 'high' },
  { title: 'Crítica',  value: 'critical' },
];

const tasks = ref<{ id: number; title: string; subtitle: string }[]>([]);

onMounted(async () => {
  const pid = props.projectId;
  if (!pid || isNaN(pid)) return;
  const response = await tasksService.active(pid);
  if (response.status && response.items) {
    tasks.value = response.items.map(t => ({
      id: t.id,
      title: t.title,
      subtitle: TASK_STATUS_LABELS[t.status ?? ''] ?? (t.status ?? ''),
    }));
  }
});

const TASK_STATUS_LABELS: Record<string, string> = {
  pending: 'Pendiente', in_progress: 'En Progreso', review: 'En Revisión', blocked: 'Bloqueada',
};
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
          <VSelect
            v-model="form.task_id"
            :error-messages="errores.task_id"
            :items="tasks"
            item-title="title"
            item-value="id"
            item-subtitle="subtitle"
            name="task_id"
            label="Tarea asociada"
            placeholder="Selecciona una tarea"
            clearable
            eager
          />
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
