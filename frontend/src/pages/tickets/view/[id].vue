<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { useAuthStore } from '@/store/useAuthStore';
import { canAction } from '@/helpers/canAction';
import { useEnsureCurrentProject } from '@/composables/useEnsureCurrentProject';
import * as ticketsService from '@/services/tickets.service';
import * as ticketCommentsService from '@/services/ticket-comments.service';
import type { TicketI } from '@/interfaces/TicketI';
import type { TicketCommentI, TicketCommentErroresFormI } from '@/interfaces/TicketCommentI';
import DocumentManager from '@/components/common/DocumentManager.vue';
import TicketCommentForm from '@/components/tickets/TicketCommentForm.vue';

useEnsureCurrentProject();

const route = useRoute();
const appStore = useAppStore();
const authStore = useAuthStore();
const { loader } = storeToRefs(appStore);

const ticket = ref<TicketI | null>(null);
const projectId = Number(route.params.projectId);
const id = Number(route.params.id);

// Habilitar la subida de adjuntos: el backend es la fuente de verdad
// (`field_permissions.add_attachments`). El cliente (creador) puede agregar
// evidencia mientras el ticket no esté cerrado.
const canUploadAttachments = computed(() => {
    const t = ticket.value as any;
    if (!t) return false;
    const canAdd = t.field_permissions?.add_attachments;
    if (typeof canAdd === 'boolean') return canAdd;
    // Fallback si el API no expone el flag: creador del ticket mientras no esté cerrado.
    if (t.status === 'closed') return false;
    return canAction('ticket.manage-attachments') || canAction('ticket.edit-own', t.created_by ?? null);
});

const statusLabels: Record<string, string> = { open: 'Abierto', in_progress: 'En progreso', resolved: 'Resuelto', closed: 'Cerrado' };
const statusColors: Record<string, string> = { open: 'error', in_progress: 'warning', resolved: 'success', closed: 'grey' };
const priorityLabels: Record<string, string> = { low: 'Baja', medium: 'Media', high: 'Alta', critical: 'Crítica' };

// ─── Seguimiento (comentarios) ──────────────────────────────────────────────
const comments = ref<TicketCommentI[]>([]);
const commentsLoading = ref(false);
const commentSending = ref(false);
const commentForm = ref<TicketCommentI>({ id: 0, ticket_id: id, user_id: 0, comment: '' });
const commentErrores = ref<TicketCommentErroresFormI>({ ticket_id: [], user_id: [], comment: [] });

async function loadComments() {
    commentsLoading.value = true;
    try {
        const response = await ticketCommentsService.index(projectId, id);
        if (response.status && response.items) {
            comments.value = response.items;
        }
    } finally {
        commentsLoading.value = false;
    }
}

async function submitComment() {
    if (commentSending.value || !commentForm.value.comment.trim()) return;
    commentSending.value = true;
    try {
        const response = await ticketCommentsService.store(projectId, id, { comment: commentForm.value.comment });
        if (response.status) {
            commentForm.value.comment = '';
            commentErrores.value = { ticket_id: [], user_id: [], comment: [] };
            await loadComments();
        } else {
            if ('errors' in response && response.errors) {
                commentErrores.value = response.errors as TicketCommentErroresFormI;
            }
            appStore.snackbar = { show: true, text: response.message, color: 'error' };
        }
    } finally {
        commentSending.value = false;
    }
}

async function removeComment(comment: TicketCommentI) {
    if (!confirm('¿Eliminar este comentario?')) return;
    const response = await ticketCommentsService.destroy(projectId, id, comment.id);
    if (response.status) {
        await loadComments();
    } else {
        appStore.snackbar = { show: true, text: response.message, color: 'error' };
    }
}

function canDeleteComment(comment: TicketCommentI): boolean {
    return comment.user_id === authStore.authUser?.id || authStore.isSuperAdmin;
}

async function loadTicket() {
    const response = await ticketsService.show(projectId, id);
    if (response.status && response.items) {
        ticket.value = response.items as TicketI;
    }
}

onMounted(async () => {
    loader.value = true;
    await loadTicket();
    await loadComments();
    loader.value = false;
});
</script>

<template>
    <VRow v-if="ticket">
        <VCol cols="12">
            <VCard>
                <VCardItem>
                    <VCardTitle class="d-flex justify-space-between flex-wrap align-center">
                        <span class="d-flex align-center gap-2">
                            <VIcon icon="ri-coupon-line" color="primary" />
                            Ticket: {{ ticket.subject }}
                        </span>
                        <div class="d-flex gap-2">
                            <VBtn variant="outlined" prepend-icon="ri-arrow-left-line"
                                :to="{ name: 'tickets', params: { projectId } }">Volver</VBtn>
                            <VBtn v-if="canAction(['ticket.edit-any', 'ticket.edit-own'], ticket.created_by)"
                                variant="tonal" color="warning" :to="{ name: 'tickets-id', params: { projectId, id } }"
                                prepend-icon="ri-pencil-line">Editar
                            </VBtn>
                        </div>
                    </VCardTitle>
                </VCardItem>
                <VDivider />
                <VCardText>
                    <VRow>
                        <VCol cols="12" md="4">
                            <div class="text-caption text-medium-emphasis">Estado</div>
                            <VChip :color="statusColors[ticket.status] ?? 'grey'" size="small" class="mt-1">
                                {{ statusLabels[ticket.status] ?? ticket.status }}
                            </VChip>
                        </VCol>
                        <VCol cols="12" md="4">
                            <div class="text-caption text-medium-emphasis">Prioridad</div>
                            <div class="text-body-1 mt-1">{{ priorityLabels[ticket.priority ?? ''] ?? ticket.priority ??
                                '—' }}</div>
                        </VCol>
                        <VCol cols="12" md="4">
                            <div class="text-caption text-medium-emphasis">Asignado a</div>
                            <div class="text-body-1 mt-1">{{ (ticket as any).assignee?.name ?? '—' }}</div>
                        </VCol>
                        <VCol cols="12" class="mt-3">
                            <div class="text-caption text-medium-emphasis">Descripción</div>
                            <div class="text-body-2 mt-1 rich-view" v-html="ticket.description || 'Sin descripción'"></div>
                        </VCol>
                    </VRow>
                </VCardText>
            </VCard>
        </VCol>
        <VCol cols="12">
            <DocumentManager parent-type="tickets" :parent-id="ticket.id" :attachments="ticket.attachments ?? []"
                :can-manage="canAction('ticket.manage-attachments')" :can-upload="canUploadAttachments"
                @refresh="loadTicket" />
        </VCol>

        <!-- Seguimiento (comentarios) -->
        <VCol cols="12">
            <VCard>
                <VCardItem>
                    <VCardTitle class="text-h6">Seguimiento</VCardTitle>
                </VCardItem>
                <VDivider />
                <VCardText>
                    <div v-if="commentsLoading" class="d-flex justify-center pa-4">
                        <VProgressCircular indeterminate color="primary" size="28" />
                    </div>
                    <template v-else>
                        <div v-if="comments.length === 0" class="text-medium-emphasis pa-2">
                            Sin comentarios de seguimiento aún.
                        </div>
                        <VList v-else lines="three" class="pa-0">
                            <VListItem v-for="comment in comments" :key="comment.id" class="px-0">
                                <template #prepend>
                                    <VAvatar size="36" color="primary" variant="tonal">
                                        <span style="font-size: 0.7rem; font-weight: 700;">
                                            {{ (comment.user?.name ?? 'U').split(' ').slice(0, 2).map((w: string) => w[0]).join('').toUpperCase() }}
                                        </span>
                                    </VAvatar>
                                </template>
                                <VListItemTitle class="d-flex align-center gap-2 flex-wrap">
                                    <strong>{{ comment.user?.name ?? 'Usuario' }}</strong>
                                    <span class="text-caption text-medium-emphasis">
                                        {{ comment.created_at ? new Date(comment.created_at).toLocaleString('es-MX') : '' }}
                                    </span>
                                </VListItemTitle>
                                <VListItemSubtitle class="text-body-2" style="white-space: pre-wrap;">
                                    {{ comment.comment }}
                                </VListItemSubtitle>
                                <template #append>
                                    <VBtn v-if="canDeleteComment(comment)" icon="ri-delete-bin-line" size="small"
                                        variant="text" color="error" @click="removeComment(comment)" />
                                </template>
                            </VListItem>
                        </VList>
                    </template>

                    <VDivider class="my-4" />

                    <form @submit.prevent="submitComment">
                        <TicketCommentForm :form="commentForm" :errores="commentErrores" />
                    </form>
                </VCardText>
            </VCard>
        </VCol>
    </VRow>
    <VRow v-else>
        <VCol cols="12" class="d-flex justify-center pa-8">
            <VProgressCircular indeterminate color="primary" />
        </VCol>
    </VRow>
</template>