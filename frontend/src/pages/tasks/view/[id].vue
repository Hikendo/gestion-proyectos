<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { canAction } from '@/helpers/canAction';
import * as tasksService from '@/services/project-tasks.service';
import type { TaskI } from '@/interfaces/TaskI';
import { formatDate } from '@/utils/util';
import DocumentManager from '@/components/common/DocumentManager.vue';

const route = useRoute();
const router = useRouter();
const appStore = useAppStore();
const { loader } = storeToRefs(appStore);

const task = ref<TaskI | null>(null);
const projectId = Number(route.params.projectId);
const id = Number(route.params.id);

const statusLabels: Record<string, string> = {
    pending: 'Pendiente', in_progress: 'En progreso', review: 'Revisión', done: 'Completada', blocked: 'Bloqueada',
};
const priorityLabels: Record<string, string> = {
    low: 'Baja', medium: 'Media', high: 'Alta', critical: 'Crítica',
};

onMounted(async () => {
    loader.value = true;
    const response = await tasksService.show(projectId, id);
    if (response.status && response.items) {
        task.value = response.items as TaskI;
    }
    loader.value = false;
});
</script>

<template>
    <VRow v-if="task">
        <VCol cols="12">
            <VCard>
                <VCardItem>
                    <VCardTitle class="d-flex justify-space-between flex-wrap align-center">
                        <span class="d-flex align-center gap-2">
                            <VIcon icon="mdi-check-circle-outline" color="primary" />
                            Tarea: {{ task.title }}
                        </span>
                        <div class="d-flex gap-2">
                            <VBtn variant="outlined" prepend-icon="mdi-arrow-left"
                                :to="{ name: 'tasks', params: { projectId } }">Volver</VBtn>
                            <VBtn v-if="canAction('Tarea.Update')" variant="tonal" color="warning"
                                :to="{ name: 'tasks-id', params: { projectId, id } }" prepend-icon="mdi-pencil">Editar
                            </VBtn>
                        </div>
                    </VCardTitle>
                </VCardItem>
                <VDivider />
                <VCardText>
                    <VRow>
                        <VCol cols="12" md="4">
                            <div class="text-caption text-medium-emphasis">Estado</div>
                            <VChip
                                :color="task.status === 'done' ? 'success' : task.status === 'blocked' ? 'error' : 'info'"
                                size="small" class="mt-1">
                                {{ statusLabels[task.status] ?? task.status }}
                            </VChip>
                        </VCol>
                        <VCol cols="12" md="4">
                            <div class="text-caption text-medium-emphasis">Prioridad</div>
                            <div class="text-body-1 mt-1">{{ priorityLabels[task.priority ?? ''] ?? task.priority ?? '—'
                                }}</div>
                        </VCol>
                        <VCol cols="12" md="4">
                            <div class="text-caption text-medium-emphasis">Fecha límite</div>
                            <div class="text-body-1 mt-1">{{ formatDate(task.due_date) ?? '—' }}</div>
                        </VCol>
                        <VCol cols="12" class="mt-3">
                            <div class="text-caption text-medium-emphasis">Descripción</div>
                            <div class="text-body-2 mt-1">{{ task.description || 'Sin descripción' }}</div>
                        </VCol>
                        <VCol cols="12" md="4">
                            <div class="text-caption text-medium-emphasis">Horas estimadas</div>
                            <div class="text-body-1 mt-1">{{ task.estimated_hours ?? '—' }}</div>
                        </VCol>
                        <VCol cols="12" md="4">
                            <div class="text-caption text-medium-emphasis">Progreso</div>
                            <div class="mt-1">
                                <VProgressLinear :model-value="task.progress ?? 0" color="primary" height="8" rounded />
                                <span class="text-caption">{{ task.progress ?? 0 }}%</span>
                            </div>
                        </VCol>
                        <VCol cols="12" md="4">
                            <div class="text-caption text-medium-emphasis">Asignado a</div>
                            <div class="text-body-1 mt-1">{{ (task as any).assignee?.name ?? '—' }}</div>
                        </VCol>
                    </VRow>
                </VCardText>
            </VCard>
        </VCol>
        <VCol cols="12">
            <DocumentManager parent-type="tasks" :parent-id="task.id" :attachments="task.attachments ?? []"
                :can-manage="canAction('Tarea.Update')" @refresh="onMounted(() => { })" />
        </VCol>
    </VRow>
    <VRow v-else>
        <VCol cols="12" class="d-flex justify-center pa-8">
            <VProgressCircular indeterminate color="primary" />
        </VCol>
    </VRow>
</template>