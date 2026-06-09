<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { canAction } from '@/helpers/canAction';
import * as ticketsService from '@/services/tickets.service';
import type { TicketI } from '@/interfaces/TicketI';
import DocumentManager from '@/components/common/DocumentManager.vue';

const route = useRoute();
const appStore = useAppStore();
const { loader } = storeToRefs(appStore);

const ticket = ref<TicketI | null>(null);
const projectId = Number(route.params.projectId);
const id = Number(route.params.id);

const statusLabels: Record<string, string> = { open: 'Abierto', in_progress: 'En progreso', resolved: 'Resuelto', closed: 'Cerrado' };
const statusColors: Record<string, string> = { open: 'error', in_progress: 'warning', resolved: 'success', closed: 'grey' };
const priorityLabels: Record<string, string> = { low: 'Baja', medium: 'Media', high: 'Alta', critical: 'Crítica' };

onMounted(async () => {
    loader.value = true;
    const response = await ticketsService.show(projectId, id);
    if (response.status && response.items) {
        ticket.value = response.items as TicketI;
    }
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
                            <VBtn v-if="canAction('Ticket.Update')" variant="tonal" color="warning"
                                :to="{ name: 'tickets-id', params: { projectId, id } }" prepend-icon="ri-pencil-line">Editar
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
                            <div class="text-body-2 mt-1">{{ ticket.description || 'Sin descripción' }}</div>
                        </VCol>
                    </VRow>
                </VCardText>
            </VCard>
        </VCol>
        <VCol cols="12">
            <DocumentManager parent-type="tickets" :parent-id="ticket.id" :attachments="ticket.attachments ?? []"
                :can-manage="canAction('Ticket.Update')" @refresh="onMounted(() => { })" />
        </VCol>
    </VRow>
    <VRow v-else>
        <VCol cols="12" class="d-flex justify-center pa-8">
            <VProgressCircular indeterminate color="primary" />
        </VCol>
    </VRow>
</template>