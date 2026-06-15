<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { canAction } from '@/helpers/canAction';
import { useEnsureCurrentProject } from '@/composables/useEnsureCurrentProject';
import * as service from '@/services/project-risks.service';
import type { RiskI } from '@/interfaces/RiskI';

useEnsureCurrentProject();

const route = useRoute();
const appStore = useAppStore();
const { loader } = storeToRefs(appStore);
const item = ref<RiskI | null>(null);
const projectId = Number(route.params.projectId);
const id = Number(route.params.id);
const impactLabels: Record<string, string> = { low: 'Bajo', medium: 'Medio', high: 'Alto', critical: 'Crítico' };

onMounted(async () => {
    loader.value = true;
    const r = await service.index(projectId);
    if (r.status && r.items) {
        const items = (r.items as any).data ?? (Array.isArray(r.items) ? r.items : []);
        item.value = items.find((x: RiskI) => x.id === id) ?? null;
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
                            <VIcon icon="ri-error-warning-line" color="error" />Riesgo: {{ item.title }}
                        </span>
                        <div class="d-flex gap-2">
                            <VBtn variant="outlined" prepend-icon="ri-arrow-left-line"
                                :to="{ name: 'risks', params: { projectId } }">Volver</VBtn>
                            <VBtn v-if="canAction('risk.edit')" variant="tonal" color="warning"
                                :to="{ name: 'risks-id', params: { projectId, id } }" prepend-icon="ri-pencil-line">
                                Editar
                            </VBtn>
                        </div>
                    </VCardTitle>
                </VCardItem>
                <VDivider />
                <VCardText>
                    <VRow>
                        <VCol cols="12" md="4">
                            <div class="text-caption text-medium-emphasis">Impacto</div>
                            <VChip
                                :color="item.impact === 'critical' ? 'error' : item.impact === 'high' ? 'warning' : 'info'"
                                size="small" class="mt-1">{{ impactLabels[item.impact] ?? item.impact }}</VChip>
                        </VCol>
                        <VCol cols="12" md="4">
                            <div class="text-caption text-medium-emphasis">Probabilidad</div>
                            <VChip
                                :color="item.probability === 'high' ? 'error' : item.probability === 'medium' ? 'warning' : 'success'"
                                size="small" class="mt-1">{{ item.probability }}</VChip>
                        </VCol>
                        <VCol cols="12" class="mt-3">
                            <div class="text-caption text-medium-emphasis">Descripción</div>
                            <div class="text-body-2 mt-1">{{ item.description || 'Sin descripción' }}</div>
                        </VCol>
                        <VCol cols="12" class="mt-3">
                            <div class="text-caption text-medium-emphasis">Plan de mitigación</div>
                            <div class="text-body-2 mt-1">{{ item.mitigation_plan || 'No definido' }}</div>
                        </VCol>
                    </VRow>
                </VCardText>
            </VCard>
        </VCol>
    </VRow>
</template>