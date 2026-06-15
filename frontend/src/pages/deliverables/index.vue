<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { canAction } from '@/helpers/canAction';
import { useEnsureCurrentProject } from '@/composables/useEnsureCurrentProject';
import * as deliverablesService from '@/services/project-deliverables.service';

useEnsureCurrentProject();
import type { DeliverableI } from '@/interfaces/DeliverableI';
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

const paginacionYquery = ref<PaginacionYQueryI>({ page: 1, query: '', last_page: 1 });
const data = ref<DeliverableI[]>([]);

const handleGetData = async () => {
  loader.value = true;
  const response = await deliverablesService.index(projectId());
  if (response.status && response.items) {
    data.value = (response.items as any).data ?? response.items;
    paginacionYquery.value.last_page = (response.items as any).last_page ?? 1;
  }
  loader.value = false;
};

// ── Toggle aprobado ───────────────────────────────────────────────────────────

async function toggleApproved(item: DeliverableI) {
  const newVal = !item.approved;
  let response;
  if (newVal) {
    response = await deliverablesService.approve(item.project_id, item.id);
  } else {
    response = await deliverablesService.update(item.project_id, item.id, {
      ...item,
      approved: false,
    });
  }
  if (response.status) {
    item.approved = newVal;
    snackbar.value = {
      show: true,
      text: newVal ? 'Entregable aprobado' : 'Entregable reabierto',
      color: newVal ? 'success' : 'warning',
    };
  } else {
    snackbar.value = { show: true, text: 'Error al actualizar', color: 'error' };
  }
}

// ── Kanban ────────────────────────────────────────────────────────────────────

const KANBAN_COLUMNS = [
  { id: 'pending', approved: false, title: 'Pendiente', color: 'warning', icon: 'ri-time-line' },
  { id: 'approved', approved: true, title: 'Aprobado', color: 'success', icon: 'ri-verified-badge-line' },
];

const getByApproved = (approved: boolean) => data.value.filter(d => d.approved === approved);

const draggedDeliverable = ref<DeliverableI | null>(null);
const dragOverColumn = ref<string | null>(null);

function onDragStart(e: DragEvent, deliverable: DeliverableI) {
  draggedDeliverable.value = deliverable;
  e.dataTransfer?.setData('text/plain', String(deliverable.id));
  if (e.dataTransfer) e.dataTransfer.effectAllowed = 'move';
}

function onDragEnd() {
  draggedDeliverable.value = null;
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
  if (!draggedDeliverable.value || draggedDeliverable.value.approved === col.approved) {
    draggedDeliverable.value = null;
    return;
  }
  const deliverable = draggedDeliverable.value;
  draggedDeliverable.value = null;

  let response;
  if (col.approved) {
    response = await deliverablesService.approve(deliverable.project_id, deliverable.id);
  } else {
    response = await deliverablesService.update(deliverable.project_id, deliverable.id, {
      ...deliverable,
      approved: false,
    });
  }
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
              <h4 class="text-h4 text-wrap me-3">Listado de <strong>Entregables</strong></h4>
              <div class="d-flex align-center gap-3">
                <VSelect v-model="viewMode" :items="viewOptions" item-title="title" item-value="value" density="compact"
                  variant="solo" flat hide-details style="max-width: 130px;" />
                <VBtn variant="flat" :to="{ name: 'deliverables-new', params: { projectId: projectId() } }"
                  v-if="canAction('deliverable.create')">
                  Nuevo entregable
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
                  <th class="text-uppercase">Nombre</th>
                  <th class="text-uppercase">Entrega</th>
                  <th class="text-uppercase">Aprobado</th>
                  <th class="text-uppercase">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in data" :key="item.id">
                  <td>{{ item.name }}</td>
                  <td>{{ formatDate(item.delivery_date!) ?? '—' }}</td>
                  <td>
                    <VSwitch :model-value="item.approved" color="success" hide-details density="compact"
                      :label="item.approved ? 'Aprobado' : 'Pendiente'" :disabled="!canAction('deliverable.approve')"
                      @update:model-value="toggleApproved(item)" />
                  </td>
                  <td>
                    <div class="d-flex gap-1">
                      <VBtn icon size="small" variant="text"
                        :to="{ name: 'deliverables-view', params: { projectId: projectId(), id: item.id } }">
                        <VIcon icon="ri-eye-line" color="primary" size="small" />
                      </VBtn>
                      <VBtn icon size="small" variant="flat"
                        :to="{ name: 'deliverables-id', params: { projectId: projectId(), id: item.id } }"
                        v-if="canAction('deliverable.edit')">
                        <VIcon icon="ri-pencil-line" color="warning" />
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
                  {{ getByApproved(col.approved).length }}
                </VChip>
              </VCardTitle>
            </VCardItem>
            <VDivider />
            <VCardText class="pa-2">
              <div class="d-flex flex-column gap-2">

                <!-- Tarjeta de entregable -->
                <VCard v-for="deliverable in getByApproved(col.approved)" :key="deliverable.id" variant="outlined"
                  class="deliverable-card"
                  :class="{ 'deliverable-dragging': draggedDeliverable?.id === deliverable.id }" draggable="true"
                  @dragstart="onDragStart($event, deliverable)" @dragend="onDragEnd">
                  <VCardText class="pa-3">

                    <!-- Nombre + ícono -->
                    <div class="d-flex align-center gap-2 mb-2">
                      <VIcon size="14" :color="col.color">ri-archive-line</VIcon>
                      <span class="text-body-2 font-weight-medium"
                        style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" :title="deliverable.name">{{
                          deliverable.name }}</span>
                    </div>

                    <!-- Fecha de entrega -->
                    <div class="d-flex align-center gap-1 mb-2">
                      <VIcon size="12" color="grey">ri-calendar-line</VIcon>
                      <span class="text-caption text-medium-emphasis">
                        {{ formatDate(deliverable.delivery_date!) ?? '—' }}
                      </span>
                    </div>

                    <!-- Acción editar -->
                    <div class="d-flex justify-end">
                      <VBtn size="x-small" variant="text" :color="col.color"
                        :to="{ name: 'deliverables-id', params: { projectId: deliverable.project_id, id: deliverable.id } }"
                        v-if="canAction('deliverable.edit')">
                        <VIcon size="14">ri-pencil-line</VIcon>
                      </VBtn>
                    </div>

                  </VCardText>
                </VCard>

                <!-- Zona de drop vacía -->
                <div v-if="getByApproved(col.approved).length === 0" class="empty-drop-zone"
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

  </VRow>
</template>

<style scoped>
.kanban-drop-target {
  outline: 2px dashed currentColor;
  outline-offset: -4px;
}

.deliverable-card {
  cursor: grab;
  transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
}

.deliverable-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.deliverable-card:active {
  cursor: grabbing;
}

.deliverable-dragging {
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
