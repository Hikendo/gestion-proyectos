<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { canAction } from '@/helpers/canAction';
import * as service from '@/services/project-plans.service';
import type { ProjectPlanI } from '@/interfaces/ProjectPlanI';

const route = useRoute();
const appStore = useAppStore();
const { loader } = storeToRefs(appStore);
const item = ref<ProjectPlanI | null>(null);
const projectId = Number(route.params.projectId);

onMounted(async () => {
    loader.value = true;
    const r = await service.show(projectId);
    if (r.status && r.items) {
        item.value = r.items as ProjectPlanI;
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
                            <VIcon icon="mdi-calendar-clock" color="primary" />Plan
                        </span>
                        <div class="d-flex gap-2">
                            <VBtn variant="outlined" prepend-icon="mdi-arrow-left"
                                :to="{ name: 'plans', params: { projectId } }">Volver</VBtn>
                            <VBtn v-if="canAction('Plan.Update')" variant="tonal" color="warning"
                                :to="{ name: 'plans-id', params: { projectId, id: item.id } }"
                                prepend-icon="mdi-pencil">Editar
                            </VBtn>
                        </div>
                    </VCardTitle>
                </VCardItem>
                <VDivider />
                <VCardText>
                    <VRow>
                        <VCol cols="12">
                            <div class="text-caption text-medium-emphasis">Alcance</div>
                            <div class="text-body-2 mt-1">{{ item.scope || '—' }}</div>
                        </VCol>
                        <VCol cols="12">
                            <div class="text-caption text-medium-emphasis">Requisitos</div>
                            <div class="text-body-2 mt-1">{{ item.requirements || '—' }}</div>
                        </VCol>
                        <VCol cols="12">
                            <div class="text-caption text-medium-emphasis">Notas técnicas</div>
                            <div class="text-body-2 mt-1">{{ item.technical_notes || '—' }}</div>
                        </VCol>
                    </VRow>
                </VCardText>
            </VCard>
        </VCol>
    </VRow>
</template>