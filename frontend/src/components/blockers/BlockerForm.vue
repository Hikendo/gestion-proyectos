<script setup lang="ts">
import { ref, onMounted } from 'vue';
import type { BlockerI, BlockerErroresFormI } from '@/interfaces/BlockerI';
import type { BlockerSeverity } from '@/interfaces/enums';
import * as tasksService from '@/services/project-tasks.service';
import RichTextEditor from '@/components/common/RichTextEditor.vue';

const props = defineProps<{
  form: BlockerI;
  errores: BlockerErroresFormI;
  projectId: number;
}>();

const emit = defineEmits<{
  (e: 'update:attachments', files: File[]): void;
}>();

function onFilesChanged(event: Event): void {
  const input = event.target as HTMLInputElement;
  if (input.files) {
    emit('update:attachments', Array.from(input.files));
  }
}

const severities: { title: string; value: BlockerSeverity }[] = [
  { title: 'Baja', value: 'low' },
  { title: 'Media', value: 'medium' },
  { title: 'Alta', value: 'high' },
  { title: 'Crítica', value: 'critical' },
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
      <VCardTitle class="text-h6">Datos del bloqueador</VCardTitle>
    </VCardItem>

    <VCardText class="px-8 pb-8">
      <VRow>
        <VCol cols="12" md="8">
          <VTextField v-model="form.title" :error-messages="errores.title" name="title" label="Título"
            variant="outlined" density="comfortable" placeholder="Título del bloqueador" />
        </VCol>

        <VCol cols="12" md="4">
          <VSelect v-model="form.severity" :error-messages="errores.severity" name="severity" :items="severities"
            item-title="title" item-value="value" label="Severidad" eager />
        </VCol>

        <VCol cols="12">
          <label class="v-label text-body-2 mb-1 d-block">Descripción</label>
          <RichTextEditor v-model="form.description" />
          <div v-if="errores.description?.length" class="v-messages text-error mt-1">
            {{ errores.description[0] }}
          </div>
        </VCol>

        <VCol cols="12" md="6">
          <VSelect v-model="form.task_id" :error-messages="errores.task_id" :items="tasks" item-title="title"
            item-value="id" item-subtitle="subtitle" name="task_id" label="Tarea asociada"
            placeholder="Selecciona una tarea" clearable eager />
        </VCol>

        <VCol cols="12">
          <VFileInput label="Archivos adjuntos (PDF, imágenes, ZIP, DOCX)" variant="outlined" density="comfortable"
            multiple accept=".pdf,.jpeg,.jpg,.png,.zip,.docx,.xlsx" :max-file-size="10240"
            prepend-icon="ri-attachment-2" @change="onFilesChanged" />
        </VCol>

        <VCol cols="12" class="d-flex gap-4">
          <VSpacer />
          <VBtn type="submit" color="primary" variant="flat" size="large">Guardar</VBtn>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>
