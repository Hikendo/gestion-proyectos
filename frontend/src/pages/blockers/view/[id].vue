<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { canAction } from '@/helpers/canAction';
import * as blockersService from '@/services/project-blockers.service';
import type { BlockerI } from '@/interfaces/BlockerI';
import DocumentManager from '@/components/common/DocumentManager.vue';

const route = useRoute();
const appStore = useAppStore();
const { loader } = storeToRefs(appStore);

const blocker = ref<BlockerI | null>(null);
const projectId = Number(route.params.projectId);
const id = Number(route.params.id);

const severityLabels: Record<string, string> = { low: 'Baja', medium: 'Media', high: 'Alta', critical: 'Crítica' };
const severityColors: Record<string, string> = { low: 'success', medium: 'info', high: 'warning', critical: 'error' };

onMounted(async () => {
    loader.value = true;
    const response = await blockersService.show(projectId, id);
    if (response.status && response.items) {
        blocker.value = response.items as BlockerI;
    }
    loader.value = false;
});
</script>

<template>
    <VRow v-if="blocker">
        <VCol cols="12">
            <VCard>
                <VCardItem>
                    <VCardTitle class="d-flex justify-space-between flex-wrap align-center">
                        <span class="d-flex align-center gap-2">
                            <VIcon icon="ri-forbid-line" color="error" />
                            Bloqueador: {{ blocker.title }}
                        </span>
                        <div class="d-flex gap-2">
                            <VBtn variant="outlined" prepend-icon="ri-arrow-left-line"
                                :to="{ name: 'blockers', params: { projectId } }">Volver</VBtn>
                            <VBtn v-if="canAction('Bloqueador.Update')" variant="tonal" color="warning"
                                :to="{ name: 'blockers-id', params: { projectId, id } }" prepend-icon="ri-pencil-line">
                                Editar</VBtn>
                        </div>
                    </VCardTitle>
                </VCardItem>
                <VDivider />
                <VCardText>
                    <VRow>
                        <VCol cols="12" md="4">
                            <div class="text-caption text-medium-emphasis">Severidad</div>
                            <VChip :color="severityColors[blocker.severity ?? ''] ?? 'grey'" size="small" class="mt-1">
                                {{ severityLabels[blocker.severity ?? ''] ?? blocker.severity ?? '—' }}
                            </VChip>
                        </VCol>
                        <VCol cols="12" md="4">
                            <div class="text-caption text-medium-emphasis">Estado</div>
                            <VChip :color="blocker.resolved ? 'success' : 'error'" size="small" class="mt-1">
                                {{ blocker.resolved ? 'Resuelto' : 'Activo' }}
                            </VChip>
                        </VCol>
                        <VCol cols="12" md="4">
                            <div class="text-caption text-medium-emphasis">Tarea asociada</div>
                            <div class="text-body-1 mt-1">{{ (blocker as any).task?.title ?? '—' }}</div>
                        </VCol>
                        <VCol cols="12" class="mt-3">
                            <div class="text-caption text-medium-emphasis">Descripción</div>
                            <div class="text-body-2 mt-1">{{ blocker.description || 'Sin descripción' }}</div>
                        </VCol>
                    </VRow>
                </VCardText>
            </VCard>
        </VCol>
        <VCol cols="12">
            <DocumentManager parent-type="blockers" :parent-id="blocker.id" :attachments="blocker.attachments ?? []"
                :can-manage="canAction('Bloqueador.Update')" @refresh="onMounted(() => { })" />
        </VCol>
    </VRow>
    <VRow v-else>
        <VCol cols="12" class="d-flex justify-center pa-8">
            <VProgressCircular indeterminate color="primary" />
        </VCol>
    </VRow>
</template>