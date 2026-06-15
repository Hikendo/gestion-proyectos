<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { canAction } from '@/helpers/canAction';
import { useEnsureCurrentProject } from '@/composables/useEnsureCurrentProject';
import * as tasksService from '@/services/project-tasks.service';

useEnsureCurrentProject();
import type { TaskI } from '@/interfaces/TaskI';
import type { PaginacionYQueryI } from '@/interfaces/PaginacionScoutI';
import { formatDate } from '@/utils/util';
import KanbanBoard from '@/components/KanbanBoard.vue';
import GanttChart from '@/components/GanttChart.vue';

const route = useRoute();
const router = useRouter();
const appStore = useAppStore();
const { loader, snackbar } = storeToRefs(appStore);

const projectId = () => Number(route.params.projectId);

// Vista actual: 'list', 'kanban', 'gantt'
const viewMode = ref<'list' | 'kanban' | 'gantt'>('list');

const isDialogVisible = ref<boolean>(false);
const paginacionYquery = ref<PaginacionYQueryI>({ page: 1, query: '', last_page: 1 });
const data = ref<TaskI[]>([]);

// Opciones del selector
const viewOptions = [
  { title: 'Lista', value: 'list', icon: 'ri-list-unordered' },
  { title: 'Kanban', value: 'kanban', icon: 'ri-dashboard-line' },
  { title: 'Gantt', value: 'gantt', icon: 'ri-bar-chart-horizontal-line' }
];

// Función para obtener color según prioridad
const getPriorityColor = (priority: string) => {
  const colors: Record<string, string> = {
    'low': 'success',
    'medium': 'info',
    'high': 'warning',
    'urgent': 'error'
  };
  return colors[priority?.toLowerCase()] || 'secondary';
};

// Función para obtener color según estado
const getStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    'pending': 'warning',
    'in_progress': 'info',
    'review': 'primary',
    'completed': 'success',
    'cancelled': 'error'
  };
  return colors[status?.toLowerCase()] || 'secondary';
};

// Función para obtener color según progreso y fecha de vencimiento
const getTaskColor = (task: TaskI) => {
  const progress = task.progress || 0;
  const dueDate = task.due_date ? new Date(task.due_date) : null;
  const today = new Date();

  if (progress === 100) return 'success';
  if (dueDate && dueDate < today) return 'error';
  if (dueDate) {
    const daysRemaining = Math.ceil((dueDate.getTime() - today.getTime()) / (1000 * 3600 * 24));
    if (daysRemaining <= 2 && progress < 80) return 'error';
    if (daysRemaining <= 5 && progress < 60) return 'warning';
    if (daysRemaining <= 10 && progress < 40) return 'info';
  }
  return 'primary';
};

const handleGetData = async () => {
  loader.value = true;
  const response = await tasksService.index(projectId(), {
    page: paginacionYquery.value.page,
    query: paginacionYquery.value.query
  });
  if (response.status && response.items) {
    data.value = (response.items as any).data ?? response.items;
    paginacionYquery.value.last_page = (response.items as any).last_page ?? 1;
  }
  loader.value = false;
};

const itemDestroy = ref<TaskI | null>(null);

const handleDestroy = async () => {
  if (!itemDestroy.value) return;
  loader.value = true;
  const response = await tasksService.destroy(itemDestroy.value.project_id, itemDestroy.value.id);
  if (response.status) {
    snackbar.value = { show: true, text: 'Tarea eliminada', color: 'success' };
    handleGetData();
  }
  loader.value = false;
  isDialogVisible.value = false;
};

watch(() => isDialogVisible.value, (val) => {
  if (!val) itemDestroy.value = null;
});

onMounted(handleGetData);
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardItem class="pb-4">
          <VCardTitle>
            <div class="d-flex justify-space-between flex-wrap align-center">
              <h4 class="text-h4 text-wrap me-3">Listado de <strong>Tareas</strong></h4>
              <div class="d-flex gap-3 align-center">
                <!-- Selector de vista con VSelect - VERSIÓN CORREGIDA -->
                <VSelect v-model="viewMode" :items="viewOptions" item-title="title" item-value="value" density="compact"
                  variant="solo" flat hide-details class="view-selector" style="max-width: 130px;" />

                <VBtn variant="flat" :to="{ name: 'tasks-new', params: { projectId: projectId() } }"
                  v-if="canAction('task.create')" prepend-icon="ri-add-line">
                  Nueva Tarea
                </VBtn>
              </div>
            </div>
          </VCardTitle>
        </VCardItem>
      </VCard>
    </VCol>

    <!-- Vista de Lista -->
    <VCol cols="12" v-if="viewMode === 'list'">
      <VCard>
        <VCardItem>
          <VRow class="d-flex align-center gap-4 mt-2">
            <VCol>
              <form @submit.prevent="() => { paginacionYquery.page = 1; handleGetData(); }">
                <VTextField label="Buscador" prepend-inner-icon="ri-search-line" type="search" clearable
                  v-model="paginacionYquery.query" />
              </form>
            </VCol>
          </VRow>
        </VCardItem>
        <VDivider />
        <VCardText class="pa-0">
          <VTable height="500" fixed-header>
            <thead>
              <tr>
                <th class="text-uppercase">Título</th>
                <th class="text-uppercase">Estado</th>
                <th class="text-uppercase">Prioridad</th>
                <th class="text-uppercase">Progreso</th>
                <th class="text-uppercase">Vencimiento</th>
                <th class="text-uppercase">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in data" :key="item.id">
                <td>
                  <div class="d-flex align-center gap-2">
                    <VIcon :icon="item.progress === 100 ? 'ri-checkbox-circle-fill' : 'ri-checkbox-blank-circle-line'"
                      :color="getTaskColor(item)" size="small" />
                    <strong>{{ item.title }}</strong>
                  </div>
                </td>
                <td>
                  <VChip :color="getStatusColor(item.status)" size="small" variant="flat">
                    {{ item.status?.replace('_', ' ') }}
                  </VChip>
                </td>
                <td>
                  <VChip :color="getPriorityColor(item.priority!)" size="small" variant="tonal">
                    {{ item.priority }}
                  </VChip>
                </td>
                <td>
                  <div class="d-flex align-center gap-2">
                    <VProgressLinear :model-value="item.progress || 0" :color="getTaskColor(item)" height="6" rounded
                      class="flex-grow-1" />
                    <span class="text-caption font-weight-medium">
                      {{ item.progress || 0 }}%
                    </span>
                  </div>
                </td>
                <td>
                  <div class="d-flex align-center gap-1">
                    <VIcon :color="getTaskColor(item)" size="small">
                      {{ new Date(item.due_date!) < new Date() && item.progress !== 100 ? 'ri-alert-fill'
                        : 'ri-calendar-line' }} </VIcon>
                        {{ formatDate(item.due_date!) ?? '—' }}
                  </div>
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <VBtn icon size="small" variant="text"
                      :to="{ name: 'tasks-view', params: { projectId: projectId(), id: item.id } }">
                      <VIcon icon="ri-eye-line" color="primary" size="small" />
                    </VBtn>
                    <VBtn icon size="small" variant="text"
                      :to="{ name: 'tasks-id', params: { projectId: projectId(), id: item.id } }"
                      v-if="canAction(['task.edit-content', 'task.edit-own'], item.assigned_to)">
                      <VIcon icon="ri-pencil-line" color="warning" size="small" />
                    </VBtn>
                    <VBtn icon size="small" variant="text" v-if="canAction('task.delete')"
                      @click="() => { itemDestroy = item; isDialogVisible = true; }">
                      <VIcon icon="ri-delete-bin-fill" color="error" size="small" />
                    </VBtn>
                  </div>
                </td>
              </tr>
            </tbody>
          </VTable>
        </VCardText>
      </VCard>
    </VCol>

    <!-- Vista Kanban -->
    <VCol cols="12" v-if="viewMode === 'kanban'">
      <KanbanBoard :tasks="data" @refresh="handleGetData" />
    </VCol>

    <!-- Vista Gantt -->
    <VCol cols="12" v-if="viewMode === 'gantt'">
      <GanttChart :tasks="data" />
    </VCol>

    <!-- Paginación (solo para vista lista) -->
    <VPagination v-if="viewMode === 'list'" class="mt-4 mr-3" color="primary" v-model="paginacionYquery.page"
      :total-visible="7" :length="paginacionYquery.last_page" style="margin-left: auto;"
      @update:model-value="handleGetData" />

    <!-- Diálogo de eliminación -->
    <VDialog v-model="isDialogVisible" persistent class="v-dialog-sm">
      <VCard title="Eliminar Tarea">
        <VCardText>¿Eliminar esta tarea?</VCardText>
        <VCardText class="d-flex justify-end flex-wrap gap-4">
          <VBtn variant="outlined" @click="isDialogVisible = false">Cancelar</VBtn>
          <VBtn color="error" @click="handleDestroy">Eliminar</VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </VRow>
</template>

<style scoped>
.view-selector {
  background-color: transparent;
}

/* Estilo para dispositivos móviles */
@media (max-width: 600px) {
  .view-selector {
    max-width: 110px !important;
  }

  .view-selector :deep(.v-field) {
    font-size: 0.875rem;
  }
}

.gap-2 {
  gap: 8px;
}

.gap-1 {
  gap: 4px;
}

.gap-3 {
  gap: 12px;
}

.flex-grow-1 {
  flex-grow: 1;
}
</style>
