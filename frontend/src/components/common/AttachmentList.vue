<script setup lang="ts">
import type { AttachmentI } from '@/interfaces/AttachmentI';
import { useAttachments } from '@/composables/useAttachments';

const props = defineProps<{
    attachments: AttachmentI[];
    canDelete?: boolean;
}>();

const { download, getFileIcon, formatSize, remove } = useAttachments();

function handleDownload(attachment: AttachmentI): void {
    download(attachment);
}

function handleDelete(attachment: AttachmentI): void {
    remove(attachment);
}
</script>

<template>
    <div class="attachment-list">
        <h6 v-if="attachments.length === 0" class="text-caption text-grey-darken-1 pa-4">
            No hay archivos adjuntos en este recurso.
        </h6>

        <VList v-else density="compact">
            <VListItem v-for="attachment in attachments" :key="attachment.uuid"
                :prepend-icon="getFileIcon(attachment.mime_type)" :title="attachment.original_name"
                :subtitle="formatSize(attachment.size)" class="attachment-item">
                <template #append>
                    <div class="d-flex ga-1">
                        <VBtn icon="mdi-download" size="small" variant="text" color="primary"
                            @click.stop="handleDownload(attachment)" />
                        <VBtn v-if="props.canDelete" icon="mdi-delete-outline" size="small" variant="text" color="error"
                            @click.stop="handleDelete(attachment)" />
                    </div>
                </template>
            </VListItem>
        </VList>
    </div>
</template>

<style scoped>
.attachment-list {
    max-height: 300px;
    overflow-y: auto;
}

.attachment-item {
    border-bottom: 1px solid rgba(var(--v-border-color), 0.12);
}
</style>