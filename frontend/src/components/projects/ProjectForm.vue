<script setup lang="ts">
import { ref } from 'vue';
import type { ProjectI, ProjectErroresFormI } from '@/interfaces/ProjectI';
import type { ProjectStatus } from '@/interfaces/enums';
import { useAttachments } from '@/composables/useAttachments';

const props = defineProps<{
  form: ProjectI;
  errores: ProjectErroresFormI;
}>();

const emit = defineEmits<{
  (e: 'update:attachments', files: File[]): void;
}>();

const selectedFiles = ref<File[]>([]);
const { formatSize } = useAttachments();

function onFilesChanged(event: Event): void {
  const input = event.target as HTMLInputElement;
  if (input.files) {
    selectedFiles.value = Array.from(input.files);
    emit('update:attachments', selectedFiles.value);
  }
}

const statuses: { title: string; value: ProjectStatus }[] = [
  { title: 'Planificación', value: 'planning' },
  { title: 'Activo', value: 'active' },
  { title: 'En espera', value: 'on_hold' },
  { title: 'Completado', value: 'completed' },
  { title: 'Cancelado', value: 'cancelled' },
];
</script>

<template>
  <VCard class="mb-4">
    <VCardItem>
      <VCardTitle class="text-h6">Información del proyecto</VCardTitle>
    </VCardItem>

    <VCardText class="px-8 pb-8">
      <VRow>
        <VCol cols="12" md="8">
          <VTextField v-model="form.name" :error-messages="errores.name" name="name" label="Nombre" variant="outlined"
            density="comfortable" placeholder="Nombre del proyecto" />
        </VCol>

        <VCol cols="12" md="4">
          <VTextField v-model="form.code" :error-messages="errores.code" name="code" label="Código" variant="outlined"
            density="comfortable" placeholder="PRY-001" />
        </VCol>

        <VCol cols="12">
          <VTextarea v-model="form.description" :error-messages="errores.description" name="description"
            label="Descripción" placeholder="Descripción del proyecto" rows="3" />
        </VCol>

        <VCol cols="12" md="4">
          <VSelect v-model="form.status" :error-messages="errores.status" name="status" :items="statuses"
            item-title="title" item-value="value" label="Estado" eager />
        </VCol>

        <VCol cols="12" md="4">
          <VTextField v-model="form.start_date" :error-messages="errores.start_date" name="start_date" type="date"
            label="Fecha de inicio" />
        </VCol>

        <VCol cols="12" md="4">
          <VTextField v-model="form.end_date" :error-messages="errores.end_date" name="end_date" type="date"
            label="Fecha de fin" />
        </VCol>

        <VCol cols="12" md="6">
          <VTextField v-model="form.budget" :error-messages="errores.budget" name="budget" type="number"
            label="Presupuesto" placeholder="0.00" />
        </VCol>

        <VCol cols="12" md="6">
          <div class="text-caption text-medium-emphasis mb-1">Progreso (%)</div>
          <div class="d-flex align-center gap-2">
            <VProgressLinear :model-value="form.progress ?? 0" color="primary" height="10" rounded
              class="flex-grow-1" />
            <span class="text-body-2 font-weight-medium">{{ form.progress ?? 0 }}%</span>
          </div>
          <div class="text-caption text-disabled mt-1">Calculado automáticamente por el progreso de las fases</div>
        </VCol>

        <VCol cols="12">
          <VFileInput label="Archivos adjuntos (PDF, imágenes, ZIP, DOCX)" variant="outlined" density="comfortable"
            multiple accept=".pdf,.jpeg,.jpg,.png,.zip,.docx,.xlsx" :max-file-size="10240"
            prepend-icon="ri-attachment-2" @change="onFilesChanged" />
          <div v-if="selectedFiles.length" class="mt-2">
            <p class="text-caption text-grey-darken-1 mb-2">
              Archivos seleccionados (previsualización local):
            </p>
            <VChip v-for="(file, idx) in selectedFiles" :key="idx" size="small" class="ma-1" closable
              @click:close="selectedFiles.splice(idx, 1); emit('update:attachments', selectedFiles)">
              {{ file.name }} ({{ formatSize(file.size) }})
            </VChip>
          </div>
        </VCol>

        <VCol cols="12" class="d-flex gap-4">
          <VSpacer />
          <VBtn type="submit" color="primary" variant="flat" size="large">Guardar</VBtn>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>
