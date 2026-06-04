<script setup lang="ts">
import { ref, computed } from 'vue';
import { useAttachments } from '@/composables/useAttachments';
import type { AttachmentI } from '@/interfaces/AttachmentI';

const props = defineProps<{
  projectId: number;
  attachments: AttachmentI[];
  canDelete?: boolean;
}>();

const emit = defineEmits<{
  (e: 'refresh'): void;
}>();

const {
  uploading,
  error,
  download: downloadFn,
  getFileIcon,
  formatSize,
  upload,
  remove: removeFn,
  replace: replaceFn,
} = useAttachments();

// ── Upload ──────────────────────────────────────────────────────
const fileInput = ref<HTMLInputElement | null>(null);
const dragOver = ref(false);

function triggerFileInput() {
  fileInput.value?.click();
}

async function handleFilesSelected(event: Event) {
  const input = event.target as HTMLInputElement;
  if (!input.files || input.files.length === 0) return;
  await uploadFiles(Array.from(input.files));
  input.value = '';
}

async function uploadFiles(files: File[]) {
  try {
    await upload('projects', props.projectId, files);
    emit('refresh');
  } catch {
    // error ya se maneja en el composable
  }
}

function onDragOver(e: DragEvent) {
  e.preventDefault();
  dragOver.value = true;
}

function onDragLeave() {
  dragOver.value = false;
}

async function onDrop(e: DragEvent) {
  e.preventDefault();
  dragOver.value = false;
  if (e.dataTransfer?.files?.length) {
    await uploadFiles(Array.from(e.dataTransfer.files));
  }
}

// ── Delete ──────────────────────────────────────────────────────
const deleteDialog = ref(false);
const attachmentToDelete = ref<AttachmentI | null>(null);
const deleting = ref(false);

function confirmDelete(attachment: AttachmentI) {
  attachmentToDelete.value = attachment;
  deleteDialog.value = true;
}

async function executeDelete() {
  if (!attachmentToDelete.value) return;
  deleting.value = true;
  const ok = await removeFn(attachmentToDelete.value);
  deleting.value = false;
  deleteDialog.value = false;
  attachmentToDelete.value = null;
  if (ok) {
    emit('refresh');
  }
}

// ── Replace ─────────────────────────────────────────────────────
const replaceDialog = ref(false);
const attachmentToReplace = ref<AttachmentI | null>(null);
const replaceFile = ref<File | null>(null);
const replacing = ref(false);

function confirmReplace(attachment: AttachmentI) {
  attachmentToReplace.value = attachment;
  replaceFile.value = null;
  replaceDialog.value = true;
}

function handleReplaceFileSelected(event: Event) {
  const input = event.target as HTMLInputElement;
  if (input.files && input.files.length > 0) {
    replaceFile.value = input.files[0];
  }
}

async function executeReplace() {
  if (!attachmentToReplace.value || !replaceFile.value) return;
  replacing.value = true;
  const result = await replaceFn(attachmentToReplace.value, replaceFile.value);
  replacing.value = false;
  replaceDialog.value = false;
  attachmentToReplace.value = null;
  replaceFile.value = null;
  if (result) {
    emit('refresh');
  }
}

// ── Downloa ─────────────────────────────────────────────────────
function handleDownload(attachment: AttachmentI) {
  downloadFn(attachment);
}

// ── Agrupación de archivos ──────────────────────────────────────
const groupedAttachments = computed(() => {
  return props.attachments.slice().sort((a: AttachmentI, b: AttachmentI) => {
    const dateA = a.created_at ? new Date(a.created_at).getTime() : 0;
    const dateB = b.created_at ? new Date(b.created_at).getTime() : 0;
    return dateB - dateA;
  });
});
</script>

<template>
  <section class="page-grid">
    <p class="feature-copy">
      Administra los documentos del proyecto. Sube, descarga, reemplaza o elimina archivos desde esta sección.
    </p>

    <VCard class="mt-4">
      <VCardItem>
        <div class="d-flex align-center justify-space-between flex-wrap gap-2">
          <VCardTitle class="d-flex align-center gap-2 pa-0">
            <VIcon icon="mdi-folder-zip-outline" />
            Expediente digital del proyecto
            <VChip v-if="groupedAttachments.length > 0" size="small" variant="tonal" color="primary">
              {{ groupedAttachments.length }} archivo(s)
            </VChip>
          </VCardTitle>
          <VBtn variant="tonal" color="primary" prepend-icon="mdi-upload" :loading="uploading"
            @click="triggerFileInput">
            Subir archivos
          </VBtn>
        </div>
      </VCardItem>

      <VDivider />

      <!-- Drop zone -->
      <VCardText class="drop-zone pa-4" :class="{ 'drop-zone--active': dragOver }" @dragover="onDragOver"
        @dragleave="onDragLeave" @drop="onDrop">
        <div v-if="groupedAttachments.length === 0 && !uploading" class="empty-state">
          <VIcon icon="mdi-cloud-upload-outline" size="48" class="text-medium-emphasis mb-2" />
          <p class="text-body-1 text-medium-emphasis mb-1">
            Arrastra archivos aquí o haz clic en "Subir archivos"
          </p>
          <p class="text-body-2 text-disabled">
            PDF, Word, Excel, imágenes, ZIP y más (máx. 100 MB por archivo)
          </p>
        </div>

        <!-- Loading -->
        <div v-if="uploading" class="d-flex align-center justify-center gap-2 py-4">
          <VProgressCircular indeterminate size="20" color="primary" />
          <span class="text-body-2">Subiendo archivos...</span>
        </div>

        <!-- Error -->
        <VAlert v-if="error" type="error" variant="tonal" density="compact" class="mb-4" closable>
          {{ error }}
        </VAlert>

        <!-- Hidden file input -->
        <input ref="fileInput" type="file" multiple style="display: none" @change="handleFilesSelected" />

        <!-- Archivos -->
        <VList v-if="groupedAttachments.length > 0" density="compact" class="py-0">
          <VListItem v-for="attachment in groupedAttachments" :key="attachment.uuid" class="attachment-row">
            <template #prepend>
              <VAvatar :color="getFileIcon(attachment.mime_type).includes('pdf') ? 'error' : 'primary'" variant="tonal"
                size="40">
                <VIcon :icon="getFileIcon(attachment.mime_type)" size="22" />
              </VAvatar>
            </template>

            <VListItemTitle class="font-weight-medium">
              {{ attachment.original_name }}
            </VListItemTitle>
            <VListItemSubtitle class="text-caption">
              {{ formatSize(attachment.size) }}
              <span class="text-disabled"> · {{ new Date(attachment.created_at).toLocaleDateString('es-MX') }}</span>
            </VListItemSubtitle>

            <template #append>
              <div class="d-flex ga-1">
                <!-- Download -->
                <VBtn icon="mdi-download" size="small" variant="text" color="primary"
                  @click.stop="handleDownload(attachment)" />
                <!-- Replace -->
                <VBtn icon="mdi-file-replace-outline" size="small" variant="text" color="warning"
                  @click.stop="confirmReplace(attachment)" />
                <!-- Delete -->
                <VBtn v-if="props.canDelete" icon="mdi-delete-outline" size="small" variant="text" color="error"
                  @click.stop="confirmDelete(attachment)" />
              </div>
            </template>
          </VListItem>
        </VList>
      </VCardText>
    </VCard>

    <!-- ── Delete confirmation dialog ─────────────────────────────── -->
    <VDialog v-model="deleteDialog" max-width="440">
      <VCard>
        <VCardItem>
          <VCardTitle class="d-flex align-center gap-2">
            <VIcon icon="mdi-alert-circle-outline" color="error" />
            Eliminar archivo
          </VCardTitle>
        </VCardItem>
        <VCardText>
          <p class="mb-2">
            ¿Estás seguro de eliminar
            <strong>{{ attachmentToDelete?.original_name }}</strong>?
          </p>
          <p class="text-body-2 text-medium-emphasis">
            Esta acción no se puede deshacer. El archivo se eliminará permanentemente.
          </p>
        </VCardText>
        <VDivider />
        <VCardActions class="gap-2 justify-end pa-4">
          <VBtn variant="outlined" @click="deleteDialog = false">Cancelar</VBtn>
          <VBtn color="error" :loading="deleting" @click="executeDelete">Eliminar</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- ── Replace dialog ─────────────────────────────────────────── -->
    <VDialog v-model="replaceDialog" max-width="440">
      <VCard>
        <VCardItem>
          <VCardTitle class="d-flex align-center gap-2">
            <VIcon icon="mdi-file-replace-outline" color="warning" />
            Reemplazar archivo
          </VCardTitle>
        </VCardItem>
        <VCardText>
          <p class="mb-2">
            Reemplazarás <strong>{{ attachmentToReplace?.original_name }}</strong> con un nuevo archivo.
          </p>
          <p class="text-body-2 text-medium-emphasis mb-4">
            El archivo actual se eliminará y se conservará el mismo registro en el expediente.
          </p>
          <VFileInput v-model="replaceFile" label="Selecciona el nuevo archivo" prepend-icon="mdi-file-upload-outline"
            accept="*/*" :show-size="true" variant="outlined" density="compact" />
        </VCardText>
        <VDivider />
        <VCardActions class="gap-2 justify-end pa-4">
          <VBtn variant="outlined" @click="replaceDialog = false">Cancelar</VBtn>
          <VBtn color="warning" :loading="replacing" :disabled="!replaceFile" @click="executeReplace">
            Reemplazar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </section>
</template>

<style scoped>
.feature-copy {
  color: rgba(var(--v-theme-on-background), 0.65);
  font-size: 0.9rem;
}

.drop-zone {
  min-height: 120px;
  transition: background-color 0.2s ease, border-color 0.2s ease;
  border-radius: 8px;
  border: 2px dashed rgba(var(--v-border-color), 0.3);
  margin: 0 16px 16px;
}

.drop-zone--active {
  background-color: rgba(var(--v-theme-primary), 0.05);
  border-color: rgb(var(--v-theme-primary));
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 24px 16px;
  text-align: center;
}

.attachment-row {
  padding: 10px 16px;
  border-bottom: 1px solid rgba(var(--v-border-color), 0.06);
}

.attachment-row:hover {
  background-color: rgba(var(--v-theme-primary), 0.03);
}
</style>