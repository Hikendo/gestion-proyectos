<script setup lang="ts">
import { ref, computed } from 'vue';
import { useAttachments } from '@/composables/useAttachments';
import type { AttachmentI } from '@/interfaces/AttachmentI';

const props = defineProps<{
    parentType: string;       // 'tasks' | 'tickets' | 'blockers'
    parentId: number;
    attachments: AttachmentI[];
    canManage?: boolean;
}>();

const emit = defineEmits<{
    (e: 'refresh'): void;
}>();

const {
    uploading,
    error,
    download,
    getFileIcon,
    formatSize,
    upload,
    remove: removeFn,
    replace: replaceFn,
} = useAttachments();

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
        await upload(props.parentType, props.parentId, files);
        emit('refresh');
    } catch {
        // error already handled in composable
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

// Delete
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
    if (ok) emit('refresh');
}

// Replace
const replaceDialog = ref(false);
const attachmentToReplace = ref<AttachmentI | null>(null);
const replaceFile = ref<File | null>(null);
const replacing = ref(false);

function confirmReplace(attachment: AttachmentI) {
    attachmentToReplace.value = attachment;
    replaceFile.value = null;
    replaceDialog.value = true;
}

async function executeReplace() {
    if (!attachmentToReplace.value || !replaceFile.value) return;
    replacing.value = true;
    const result = await replaceFn(attachmentToReplace.value, replaceFile.value);
    replacing.value = false;
    replaceDialog.value = false;
    attachmentToReplace.value = null;
    replaceFile.value = null;
    if (result) emit('refresh');
}

const sortedAttachments = computed(() =>
    [...props.attachments].sort(
        (a: AttachmentI, b: AttachmentI) =>
            new Date(b.created_at ?? 0).getTime() - new Date(a.created_at ?? 0).getTime()
    )
);
</script>

<template>
    <VCard>
        <VCardItem>
            <div class="d-flex align-center justify-space-between flex-wrap gap-2">
                <VCardTitle class="d-flex align-center gap-2 pa-0">
                    <VIcon icon="mdi-folder-zip-outline" />
                    Expediente digital
                    <VChip v-if="sortedAttachments.length > 0" size="small" variant="tonal" color="primary">
                        {{ sortedAttachments.length }} archivo(s)
                    </VChip>
                </VCardTitle>
                <VBtn v-if="canManage" variant="tonal" color="primary" prepend-icon="mdi-upload" :loading="uploading"
                    @click="triggerFileInput">
                    Subir archivos
                </VBtn>
            </div>
        </VCardItem>

        <VDivider />

        <VCardText class="drop-zone pa-4" :class="{ 'drop-zone--active': dragOver && canManage }"
            @dragover.prevent="canManage && (dragOver = true)" @dragleave="dragOver = false"
            @drop.prevent="canManage && onDrop">
            <div v-if="sortedAttachments.length === 0 && !uploading" class="empty-state">
                <VIcon icon="mdi-cloud-upload-outline" size="40" class="text-medium-emphasis mb-2" />
                <p class="text-body-2 text-medium-emphasis">
                    {{ canManage ? 'Arrastra archivos aquí o haz clic en "Subir archivos"' : 'No hay archivos adjuntos'
                    }}
                </p>
            </div>

            <div v-if="uploading" class="d-flex align-center justify-center gap-2 py-4">
                <VProgressCircular indeterminate size="20" color="primary" />
                <span class="text-body-2">Subiendo archivos...</span>
            </div>

            <VAlert v-if="error" type="error" variant="tonal" density="compact" class="mb-2" closable>
                {{ error }}
            </VAlert>

            <input ref="fileInput" type="file" multiple hidden @change="handleFilesSelected" />

            <VList v-if="sortedAttachments.length > 0" density="compact" class="py-0">
                <VListItem v-for="att in sortedAttachments" :key="att.uuid" class="attachment-row">
                    <template #prepend>
                        <VAvatar :color="getFileIcon(att.mime_type).includes('pdf') ? 'error' : 'primary'"
                            variant="tonal" size="36">
                            <VIcon :icon="getFileIcon(att.mime_type)" size="20" />
                        </VAvatar>
                    </template>

                    <VListItemTitle class="font-weight-medium text-body-2">{{ att.original_name }}</VListItemTitle>
                    <VListItemSubtitle class="text-caption">
                        {{ formatSize(att.size) }} · {{ new Date(att.created_at ?? '').toLocaleDateString('es-MX') }}
                    </VListItemSubtitle>

                    <template #append>
                        <div class="d-flex ga-1">
                            <VBtn icon="mdi-download" size="x-small" variant="text" color="primary"
                                @click.stop="download(att)" />
                            <VBtn v-if="canManage" icon="mdi-file-replace-outline" size="x-small" variant="text"
                                color="warning" @click.stop="confirmReplace(att)" />
                            <VBtn v-if="canManage" icon="mdi-delete-outline" size="x-small" variant="text" color="error"
                                @click.stop="confirmDelete(att)" />
                        </div>
                    </template>
                </VListItem>
            </VList>
        </VCardText>
    </VCard>

    <!-- Delete Dialog -->
    <VDialog v-model="deleteDialog" max-width="400">
        <VCard>
            <VCardItem>
                <VCardTitle class="d-flex align-center gap-2">
                    <VIcon icon="mdi-alert-circle-outline" color="error" /> Eliminar archivo
                </VCardTitle>
            </VCardItem>
            <VCardText>
                <p>¿Eliminar <strong>{{ attachmentToDelete?.original_name }}</strong>?</p>
                <p class="text-body-2 text-medium-emphasis">Esta acción no se puede deshacer.</p>
            </VCardText>
            <VDivider />
            <VCardActions class="justify-end pa-4 gap-2">
                <VBtn variant="outlined" @click="deleteDialog = false">Cancelar</VBtn>
                <VBtn color="error" :loading="deleting" @click="executeDelete">Eliminar</VBtn>
            </VCardActions>
        </VCard>
    </VDialog>

    <!-- Replace Dialog -->
    <VDialog v-model="replaceDialog" max-width="400">
        <VCard>
            <VCardItem>
                <VCardTitle class="d-flex align-center gap-2">
                    <VIcon icon="mdi-file-replace-outline" color="warning" /> Reemplazar archivo
                </VCardTitle>
            </VCardItem>
            <VCardText>
                <p class="mb-2">Reemplazar <strong>{{ attachmentToReplace?.original_name }}</strong></p>
                <VFileInput v-model="replaceFile" label="Nuevo archivo" prepend-icon="mdi-file-upload-outline"
                    accept="*/*" variant="outlined" density="compact" />
            </VCardText>
            <VDivider />
            <VCardActions class="justify-end pa-4 gap-2">
                <VBtn variant="outlined" @click="replaceDialog = false">Cancelar</VBtn>
                <VBtn color="warning" :loading="replacing" :disabled="!replaceFile" @click="executeReplace">Reemplazar
                </VBtn>
            </VCardActions>
        </VCard>
    </VDialog>
</template>

<style scoped>
.drop-zone {
    min-height: 100px;
    transition: background-color 0.2s, border-color 0.2s;
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
    padding: 20px 16px;
    text-align: center;
}

.attachment-row {
    padding: 8px 16px;
    border-bottom: 1px solid rgba(var(--v-border-color), 0.06);
}

.attachment-row:hover {
    background-color: rgba(var(--v-theme-primary), 0.03);
}
</style>