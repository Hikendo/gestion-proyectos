<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { canAction } from '@/helpers/canAction';
import * as service from '@/services/project-phases.service';
import type { ProjectPhaseI } from '@/interfaces/ProjectPhaseI';
import { formatDate } from '@/utils/util';

const route = useRoute();
const appStore = useAppStore();
const { loader } = storeToRefs(appStore);
const item = ref<ProjectPhaseI | null>(null);
const projectId = Number(route.params.projectId);
const id = Number(route.params.id);

onMounted(async () => {
    loader.value = true;
    const r = await service.index(projectId);
    if (r.status && r.items) {
        const items = (r.items as any).data ?? (Array.isArray(r.items) ? r.items : []);
        item.value = items.find((p: ProjectPhaseI) => p.id === id) ?? null;
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
                            <VIcon icon="ri-timeline-view" color="primary" />Fase: {{ item.name }}
                        </span>
                        <div class="d-flex gap-2">
                            <VBtn variant="outlined" prepend-icon="ri-arrow-left-line"
                                :to="{ name: 'phases', params: { projectId } }">Volver</VBtn>
                            <VBtn v-if="canAction('Fase.Update')" variant="tonal" color="warning"
                                :to="{ name: 'phases-id', params: { projectId, id } }" prepend-icon="ri-pencil-line">Editar
                            </VBtn>
                        </div>
                    </VCardTitle>
                </VCardItem>
                <VDivider />
                <VCardText>
                    <VRow>
                        <VCol cols="12" md="4">
                            <div class="text-caption text-medium-emphasis">Fecha inicio</div>
                            <div class="text-body-1 mt-1">{{ formatDate(item.start_date) ?? '—' }}</div>
                        </VCol>
                        <VCol cols="12" md="4">
                            <div class="text-caption text-medium-emphasis">Fecha fin</div>
                            <div class="text-body-1 mt-1">{{ formatDate(item.end_date) ?? '—' }}</div>
                        </VCol>
                        <VCol cols="12" md="4">
                            <div class="text-caption text-medium-emphasis">Progreso</div>
                            <VProgressLinear :model-value="item.progress ?? 0" color="primary" height="8" rounded
                                class="mt-1" />
                            <span class="text-caption">{{ item.progress ?? 0 }}%</span>
                        </VCol>
                    </VRow>
                </VCardText>
            </VCard>
        </VCol>
    </VRow>
</template>