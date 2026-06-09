<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { canAction } from '@/helpers/canAction';
import * as milestonesService from '@/services/project-milestones.service';
import type { MilestoneI } from '@/interfaces/MilestoneI';
import type { PaginacionYQueryI } from '@/interfaces/PaginacionScoutI';
import { formatDate } from '@/utils/util';

const route = useRoute();
const appStore = useAppStore();
const { loader, snackbar } = storeToRefs(appStore);

const projectId = () => Number(route.params.projectId);

// ── Vista ─────────────────────────────────────────────────────────────────────

const viewMode = ref<'list' | 'kanban'>('list');
const viewOptions = [
  { title: '📋 Lista', value: 'list' },
  { title: '🎯 Kanban', value: 'kanban' },
];

// ── Datos ─────────────────────────────────────────────────────────────────────

const isDialogVisible = ref<boolean>(false);
const paginacionYquery = ref<PaginacionYQueryI>({ page: 1, query: '', last_page: 1 });
const data = ref<MilestoneI[]>([]);

const handleGetData = async () => {
  loader.value = true;
  const response = await milestonesService.index(projectId());
  if (response.status && response.items) {
    data.value = (response.items as any).data ?? response.items;
    paginacionYquery.value.last_page = (response.items as any).last_page ?? 1;
  }
  loader.value = false;
};

// ── Eliminar ──────────────────────────────────────────────────────────────────

const itemDestroy = ref<MilestoneI | null>(null);

const handleDestroy = async () => {
  if (!itemDestroy.value) return;
  loader.value = true;
  const response = await milestonesService.destroy(itemDestroy.value.project_id, itemDestroy.value.id);
  if (response.status) {
    snackbar.value = { show: true, text: 'Hito eliminado', color: 'success' };
    handleGetData();
  }
  loader.value = false;
  isDialogVisible.value = false;
};

watch(() => isDialogVisible.value, (val) => {
  if (!val) itemDestroy.value = null;
});

// ── Toggle completado ─────────────────────────────────────────────────────────

async function toggleCompleted(item: MilestoneI) {
  const newVal = !item.completed;
  const response = await milestonesService.update(item.project_id, item.id, {
    ...item,
    completed: newVal,
  });
  if (response.status) {
    item.completed = newVal;
    snackbar.value = {
      show: true,
      text: newVal ? 'Hito completado' : 'Hito reabierto',
      color: newVal ? 'success' : 'warning',
    };
  } else {
    snackbar.value = { show: true, text: 'Error al actualizar', color: 'error' };
  }
}

// ── Kanban ────────────────────────────────────────────────────────────────────

const KANBAN_COLUMNS = [
  { id: 'pending', completed: false, title: 'Pendiente', color: 'warning', icon: 'ri-time-line' },
  { id: 'completed', completed: true, title: 'Completado', color: 'success', icon: 'ri-verified-badge-line' },
];

const getByCompleted = (completed: boolean) => data.value.filter(m => m.completed === completed);

const draggedMilestone = ref<MilestoneI | null>(null);
const dragOverColumn = ref<string | null>(null);

function onDragStart(e: DragEvent, milestone: MilestoneI) {
  draggedMilestone.value = milestone;
  e.dataTransfer?.setData('text/plain', String(milestone.id));
  if (e.dataTransfer) e.dataTransfer.effectAllowed = 'move';
}

function onDragEnd() {
  draggedMilestone.value = null;
  dragOverColumn.value = null;
}

function onDragEnter(colId: string) { dragOverColumn.value = colId; }

function onDragLeave(e: DragEvent, colId: string) {
  const rel = e.relatedTarget as HTMLElement | null;
  if (!(e.currentTarget as HTMLElement).contains(rel)) {
    if (dragOverColumn.value === colId) dragOverColumn.value = null;
  }
}

async function onDrop(e: DragEvent, col: typeof KANBAN_COLUMNS[0]) {
  e.preventDefault();
  dragOverColumn.value = null;
  if (!draggedMilestone.value || draggedMilestone.value.completed === col.completed) {
    draggedMilestone.value = null;
    return;
  }
  const milestone = draggedMilestone.value;
  draggedMilestone.value = null;

  const response = await milestonesService.update(milestone.project_id, milestone.id, {
    ...milestone,
    completed: col.completed,
  });
  if (response.status) {
    snackbar.value = { show: true, text: 'Estado actualizado', color: 'success' };
    handleGetData();
  } else {
    snackbar.value = { show: true, text: 'Error al actualizar', color: 'error' };
  }
}

onMounted(handleGetData);
</script>

<template>
  <VRow>

    <!-- ── Encabezado ──────────────────────────────────────────────────────── -->
    <VCol cols="12">
      <VCard>
        <VCardItem class="pb-4">
          <VCardTitle>
            <div class="d-flex justify-space-between align-center flex-wrap gap-3">
              <h4 class="text-h4 text-wrap me-3">Listado de <strong>Hitos</strong></h4>
              <div class="d-flex align-center gap-3">
                <VSelect v-model="viewMode" :items="viewOptions" item-title="title" item-value="value" density="compact"
                  variant="solo" flat hide-details style="max-width: 130px;" />
                <VBtn variant="flat" :to="{ name: 'milestones-new', params: { projectId: projectId() } }"
                  v-if="canAction('Hito.Store')">
                  Nuevo hito
                </VBtn>
              </div>
            </div>
          </VCardTitle>
        </VCardItem>
      </VCard>
    </VCol>

    <!-- ── Vista Lista ────────────────────────────────────────────────────── -->
    <template v-if="viewMode === 'list'">
      <VCol cols="12">
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
                  <th class="text-uppercase">Fecha objetivo</th>
                  <th class="text-uppercase">Completado</th>
                  <th class="text-uppercase">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in data" :key="item.id">
                  <td>{{ item.title }}</td>
                  <td>{{ formatDate(item.target_date!) ?? '—' }}</td>
                  <td>
                    <VSwitch :model-value="item.completed" color="success" hide-details density="compact"
                      :label="item.completed ? 'Completado' : 'Pendiente'"
                      @update:model-value="toggleCompleted(item)" />
                  </td>
                  <td>
                    <div class="d-flex gap-1">
                      <VBtn icon size="small" variant="text"
                        :to="{ name: 'milestones-view', params: { projectId: projectId(), id: item.id } }">
                        <VIcon icon="ri-eye-line" color="primary" size="small" />
                      </VBtn>
                      <VBtn icon size="small" variant="flat"
                        :to="{ name: 'milestones-id', params: { projectId: projectId(), id: item.id } }"
                        v-if="canAction('Hito.Update')">
                        <VIcon icon="ri-pencil-line" color="warning" />
                      </VBtn>
                      <VBtn icon size="small" variant="flat" v-if="canAction('Hito.Destroy')"
                        @click="() => { itemDestroy = item; isDialogVisible = true; }">
                        <VIcon icon="ri-delete-bin-fill" color="error" />
                      </VBtn>
                    </div>
                  </td>
                </tr>
              </tbody>
            </VTable>
          </VCardText>
        </VCard>
      </VCol>

      <VPagination class="mt-4 mr-3" color="primary" v-model="paginacionYquery.page" :total-visible="7"
        :length="paginacionYquery.last_page" style="margin-left: auto;" @update:model-value="handleGetData" />
    </template>

    <!-- ── Vista Kanban ───────────────────────────────────────────────────── -->
    <VCol cols="12" v-if="viewMode === 'kanban'">
      <VRow style="flex-wrap: nowrap;" class="overflow-x-auto pb-4">
        <VCol v-for="col in KANBAN_COLUMNS" :key="col.id" style="min-width: 320px;" @dragover.prevent
          @dragenter.prevent="onDragEnter(col.id)" @dragleave="onDragLeave($event, col.id)" @drop="onDrop($event, col)">
          <VCard :color="col.color" variant="tonal" class="h-100"
            :class="{ 'kanban-drop-target': dragOverColumn === col.id }">
            <VCardItem>
              <VCardTitle class="d-flex align-center gap-2">
                <VIcon :icon="col.icon" :color="col.color" size="18" />
                <span class="text-body-1 font-weight-bold">{{ col.title }}</span>
                <VChip size="x-small" color="grey" class="ml-1">
                  {{ getByCompleted(col.completed).length }}
                </VChip>
              </VCardTitle>
            </VCardItem>
            <VDivider />
            <VCardText class="pa-2">
              <div class="d-flex flex-column gap-2">

                <!-- Tarjeta de hito -->
                <VCard v-for="milestone in getByCompleted(col.completed)" :key="milestone.id" variant="outlined"
                  class="milestone-card" :class="{ 'milestone-dragging': draggedMilestone?.id === milestone.id }"
                  draggable="true" @dragstart="onDragStart($event, milestone)" @dragend="onDragEnd">
                  <VCardText class="pa-3">

                    <!-- Título -->
                    <div class="d-flex align-center gap-2 mb-2">
                      <VIcon size="14" :color="col.color">ri-flag-line</VIcon>
                      <span class="text-body-2 font-weight-medium"
                        style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" :title="milestone.title">{{
                          milestone.title }}</span>
                    </div>

                    <!-- Fecha objetivo -->
                    <div class="d-flex align-center gap-1 mb-2">
                      <VIcon size="12" color="grey">ri-calendar-line</VIcon>
                      <span class="text-caption text-medium-emphasis">
                        {{ formatDate(milestone.target_date!) ?? '—' }}
                      </span>
                    </div>

                    <!-- Acciones -->
                    <div class="d-flex justify-end gap-1">
                      <VBtn size="x-small" variant="text" :color="col.color"
                        :to="{ name: 'milestones-id', params: { projectId: milestone.project_id, id: milestone.id } }"
                        v-if="canAction('Hito.Update')">
                        <VIcon size="14">ri-pencil-line</VIcon>
                      </VBtn>
                      <VBtn size="x-small" variant="text" color="error" v-if="canAction('Hito.Destroy')"
                        @click="() => { itemDestroy = milestone; isDialogVisible = true; }">
                        <VIcon size="14">ri-delete-bin-fill</VIcon>
                      </VBtn>
                    </div>

                  </VCardText>
                </VCard>

                <!-- Zona de drop vacía -->
                <div v-if="getByCompleted(col.completed).length === 0" class="empty-drop-zone"
                  :class="{ 'empty-drop-zone--active': dragOverColumn === col.id }">
                  <VIcon size="28" color="grey-lighten-1">ri-inbox-unarchive-line</VIcon>
                  <span class="text-caption text-grey">Arrastra aquí</span>
                </div>

              </div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>
    </VCol>

    <!-- ── Diálogo eliminar ───────────────────────────────────────────────── -->
    <VDialog v-model="isDialogVisible" persistent class="v-dialog-sm">
      <VCard title="Eliminar Hito">
        <VCardText>¿Eliminar este hito?</VCardText>
        <VCardText class="d-flex justify-end flex-wrap gap-4">
          <VBtn variant="outlined" @click="isDialogVisible = false">Cancelar</VBtn>
          <VBtn color="error" @click="handleDestroy">Eliminar</VBtn>
        </VCardText>
      </VCard>
    </VDialog>

  </VRow>
</template>

<style scoped>
.kanban-drop-target {
  outline: 2px dashed currentColor;
  outline-offset: -4px;
}

.milestone-card {
  cursor: grab;
  transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
}

.milestone-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.milestone-card:active {
  cursor: grabbing;
}

.milestone-dragging {
  opacity: 0.4;
}

.empty-drop-zone {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  min-height: 72px;
  border: 2px dashed rgba(0, 0, 0, 0.12);
  border-radius: 8px;
  padding: 16px;
  transition: border-color 0.2s ease, background-color 0.2s ease;
}

.empty-drop-zone--active {
  border-color: currentColor;
  background-color: rgba(0, 0, 0, 0.04);
}
</style>
