<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { canAction } from '@/helpers/canAction';
import * as service from '@/services/project-deliverables.service';
import type { DeliverableI } from '@/interfaces/DeliverableI';
import { formatDate } from '@/utils/util';

const route = useRoute();
const appStore = useAppStore();
const { loader } = storeToRefs(appStore);
const item = ref<DeliverableI | null>(null);
const projectId = Number(route.params.projectId);
const id = Number(route.params.id);

onMounted(async () => {
    loader.value = true;
    const r = await service.index(projectId);
    if (r.status && r.items) {
        const items = (r.items as any).data ?? (Array.isArray(r.items) ? r.items : []);
        item.value = items.find((d: DeliverableI) => d.id === id) ?? null;
    }
    loader.value = false;
});
</script>

<template>
    <VRow v-if="item">
        <VCol cols="12">
            <VCard>
                <VCardItem>
                    <VCardTitle class="d-flex justify-space-between flex-wrap align-center">
                        <span class="d-flex align-center gap-2">
                            <VIcon icon="ri-archive-line" color="primary" />Entregable: {{ item.name }}
                        </span>
                        <div class="d-flex gap-2">
                            <VBtn variant="outlined" prepend-icon="ri-arrow-left-line"
                                :to="{ name: 'deliverables', params: { projectId } }">Volver</VBtn>
                            <VBtn v-if="canAction('deliverable.edit')" variant="tonal" color="warning"
                                :to="{ name: 'deliverables-id', params: { projectId, id } }"
                                prepend-icon="ri-pencil-line">
                                Editar</VBtn>
                        </div>
                    </VCardTitle>
                </VCardItem>
                <VDivider />
                <VCardText>
                    <VRow>
                        <VCol cols="12" md="4">
                            <div class="text-caption text-medium-emphasis">Fecha entrega</div>
                            <div class="text-body-1 mt-1">{{ formatDate(item.delivery_date!) ?? '—' }}</div>
                        </VCol>
                        <VCol cols="12" md="4">
                            <div class="text-caption text-medium-emphasis">Aprobado</div>
                            <VChip :color="item.approved ? 'success' : 'warning'" size="small" class="mt-1">{{
                                item.approved ? 'Sí' : 'No' }}</VChip>
                        </VCol>
                        <VCol cols="12" class="mt-3">
                            <div class="text-caption text-medium-emphasis">Descripción</div>
                            <div class="text-body-2 mt-1">{{ item.description || 'Sin descripción' }}</div>
                        </VCol>
                    </VRow>
                </VCardText>
            </VCard>
        </VCol>
    </VRow>
</template>