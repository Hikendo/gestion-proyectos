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
      <h5 class="text-h5 text-wrap">Información del proyecto</h5>
    </VCardItem>

    <VCardText class="px-8 pb-8">
      <VRow>
        <VCol cols="12" md="8">
          <VTextField v-model="form.name" :error-messages="errores.name" name="name" label="Nombre"
            placeholder="Nombre del proyecto" />
        </VCol>

        <VCol cols="12" md="4">
          <VTextField v-model="form.code" :error-messages="errores.code" name="code" label="Código"
            placeholder="PRY-001" />
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
          <VTextField v-model="form.progress" :error-messages="errores.progress" name="progress" type="number"
            label="Progreso (%)" placeholder="0" />
        </VCol>

        <VCol cols="12">
          <VFileInput label="Archivos adjuntos (PDF, imágenes, ZIP, DOCX)" multiple
            accept=".pdf,.jpeg,.jpg,.png,.zip,.docx,.xlsx" :max-file-size="10240" prepend-icon="mdi-paperclip"
            @change="onFilesChanged" />
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
          <VBtn type="submit">Guardar</VBtn>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>
