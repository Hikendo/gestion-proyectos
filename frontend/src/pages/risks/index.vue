<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { canAction } from '@/helpers/canAction';
import { useEnsureCurrentProject } from '@/composables/useEnsureCurrentProject';
import * as risksService from '@/services/project-risks.service';
import type { RiskI } from '@/interfaces/RiskI';

const route = useRoute();
const appStore = useAppStore();
const { loader, snackbar } = storeToRefs(appStore);

useEnsureCurrentProject();

const projectId = () => Number(route.params.projectId);

// ── Vista ─────────────────────────────────────────────────────────────────────

const viewMode = ref<'list' | 'kanban'>('list');

// ── Datos ─────────────────────────────────────────────────────────────────────

const isDialogVisible = ref<boolean>(false);
const data = ref<RiskI[]>([]);

const handleGetData = async () => {
  loader.value = true;
  const response = await risksService.index(projectId());
  if (response.status && response.items) {
    data.value = Array.isArray(response.items)
      ? response.items
      : (response.items as any).data ?? [];
  }
  loader.value = false;
};

const itemDestroy = ref<RiskI | null>(null);

const handleDestroy = async () => {
  if (!itemDestroy.value) return;
  loader.value = true;
  const response = await risksService.destroy(itemDestroy.value.project_id, itemDestroy.value.id);
  if (response.status) {
    snackbar.value = { show: true, text: 'Riesgo eliminado', color: 'success' };
    handleGetData();
  }
  loader.value = false;
  isDialogVisible.value = false;
};

// ── Kanban ────────────────────────────────────────────────────────────────────

const KANBAN_COLUMNS = [
  { id: 'active', title: 'Activo', color: 'error', icon: 'ri-error-warning-line' },
  { id: 'mitigated', title: 'Mitigado', color: 'warning', icon: 'ri-shield-cross-line' },
  { id: 'resolved', title: 'Resuelto', color: 'success', icon: 'ri-shield-check-line' },
];

const getByStatus = (status: string) => data.value.filter(r => r.status === status);

// DnD
const draggedRisk = ref<RiskI | null>(null);
const dragOverColumn = ref<string | null>(null);

function onDragStart(e: DragEvent, risk: RiskI) {
  draggedRisk.value = risk;
  e.dataTransfer?.setData('text/plain', String(risk.id));
  if (e.dataTransfer) e.dataTransfer.effectAllowed = 'move';
}

function onDragEnd() {
  draggedRisk.value = null;
  dragOverColumn.value = null;
}

function onDragEnter(colId: string) { dragOverColumn.value = colId; }

function onDragLeave(e: DragEvent, colId: string) {
  const rel = e.relatedTarget as HTMLElement | null;
  if (!(e.currentTarget as HTMLElement).contains(rel)) {
    if (dragOverColumn.value === colId) dragOverColumn.value = null;
  }
}

async function onDrop(e: DragEvent, targetColId: string) {
  e.preventDefault();
  dragOverColumn.value = null;
  if (!draggedRisk.value || draggedRisk.value.status === targetColId) {
    draggedRisk.value = null;
    return;
  }
  const risk = draggedRisk.value;
  draggedRisk.value = null;
  const response = await risksService.update(risk.project_id, risk.id, {
    ...risk,
    status: targetColId as any,
  });
  if (response.status) {
    snackbar.value = { show: true, text: 'Estado actualizado', color: 'success' };
    handleGetData();
  } else {
    snackbar.value = { show: true, text: 'Error al actualizar', color: 'error' };
  }
}

async function moveRisk(risk: RiskI, newStatus: string) {
  const response = await risksService.update(risk.project_id, risk.id, {
    ...risk,
    status: newStatus as any,
  });
  if (response.status) {
    snackbar.value = { show: true, text: 'Estado actualizado', color: 'success' };
    handleGetData();
  } else {
    snackbar.value = { show: true, text: 'Error al actualizar', color: 'error' };
  }
}

// ── Helpers de color / etiquetas ──────────────────────────────────────────────

const STATUS_COLORS: Record<string, string> = {
  active: 'error', mitigated: 'warning', resolved: 'success',
};
const STATUS_LABELS: Record<string, string> = {
  active: 'Activo', mitigated: 'Mitigado', resolved: 'Resuelto',
};
const IMPACT_COLORS: Record<string, string> = {
  low: 'success', medium: 'info', high: 'warning', critical: 'error',
};
const IMPACT_LABELS: Record<string, string> = {
  low: 'Bajo', medium: 'Medio', high: 'Alto', critical: 'Crítico',
};
const PROB_COLORS: Record<string, string> = {
  low: 'success', medium: 'warning', high: 'error',
};
const PROB_LABELS: Record<string, string> = {
  low: 'Baja', medium: 'Media', high: 'Alta',
};

watch(() => isDialogVisible.value, v => { if (!v) itemDestroy.value = null; });
onMounted(handleGetData);
const viewOptions = [
  { title: ' Lista', value: 'list' },
  { title: ' Kanban', value: 'kanban' },
];
</script>

<template>
  <VRow>

    <!-- ── Encabezado ──────────────────────────────────────────────────────── -->
    <VCol cols="12">
      <VCard>
        <VCardItem class="pb-4">
          <VCardTitle>
            <div class="d-flex justify-space-between align-center flex-wrap gap-3">
              <h4 class="text-h4 text-wrap">Listado de <strong>Riesgos</strong></h4>
              <div class="d-flex align-center gap-3">
                <!-- Selector de vista con VSelect - VERSIÓN CORREGIDA -->
                <VSelect v-model="viewMode" :items="viewOptions" item-title="title" item-value="value" density="compact"
                  variant="solo" flat hide-details class="view-selector" style="max-width: 130px;" />
                <VBtn variant="flat" prepend-icon="ri-add-line"
                  :to="{ name: 'risks-new', params: { projectId: projectId() } }" v-if="canAction('Riesgo.Store')">
                  Nuevo riesgo
                </VBtn>
              </div>
            </div>
          </VCardTitle>
        </VCardItem>
      </VCard>
    </VCol>

    <!-- ── Vista Lista ────────────────────────────────────────────────────── -->
    <VCol cols="12" v-if="viewMode === 'list'">
      <VCard>
        <VCardText class="pa-0">
          <VTable height="500" fixed-header>
            <thead>
              <tr>
                <th class="text-uppercase">Título</th>
                <th class="text-uppercase">Estado</th>
                <th class="text-uppercase">Impacto</th>
                <th class="text-uppercase">Probabilidad</th>
                <th class="text-uppercase">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in data" :key="item.id">
                <td>
                  <div class="d-flex align-center gap-2">
                    <VIcon size="16" :color="STATUS_COLORS[item.status]">ri-error-warning-line</VIcon>
                    <span class="font-weight-medium">{{ item.title }}</span>
                  </div>
                </td>
                <td>
                  <VChip :color="STATUS_COLORS[item.status]" size="small" variant="flat">
                    {{ STATUS_LABELS[item.status] ?? item.status }}
                  </VChip>
                </td>
                <td>
                  <VChip :color="IMPACT_COLORS[item.impact]" size="small" variant="tonal">
                    {{ IMPACT_LABELS[item.impact] ?? item.impact }}
                  </VChip>
                </td>
                <td>
                  <VChip :color="PROB_COLORS[item.probability]" size="small" variant="tonal">
                    {{ PROB_LABELS[item.probability] ?? item.probability }}
                  </VChip>
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <VBtn icon size="small" variant="text"
                      :to="{ name: 'risks-view', params: { projectId: projectId(), id: item.id } }">
                      <VIcon icon="ri-eye-line" color="primary" size="small" />
                    </VBtn>
                    <VBtn icon size="small" variant="text"
                      :to="{ name: 'risks-id', params: { projectId: projectId(), id: item.id } }"
                      v-if="canAction('Riesgo.Update')">
                      <VIcon icon="ri-pencil-line" color="warning" size="small" />
                    </VBtn>
                    <VBtn icon size="small" variant="text" v-if="canAction('Riesgo.Destroy')"
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

    <!-- ── Vista Kanban ───────────────────────────────────────────────────── -->
    <VCol cols="12" v-if="viewMode === 'kanban'">
      <VRow style="flex-wrap: nowrap;" class="overflow-x-auto pb-4">
        <VCol v-for="col in KANBAN_COLUMNS" :key="col.id" style="min-width: 300px;" @dragover.prevent
          @dragenter.prevent="onDragEnter(col.id)" @dragleave="onDragLeave($event, col.id)"
          @drop="onDrop($event, col.id)">
          <VCard :color="col.color" variant="tonal" class="h-100"
            :class="{ 'kanban-drop-target': dragOverColumn === col.id }">
            <VCardItem>
              <VCardTitle class="d-flex align-center gap-2">
                <VIcon :icon="col.icon" :color="col.color" size="18" />
                <span class="text-body-1 font-weight-bold">{{ col.title }}</span>
                <VChip size="x-small" color="grey" class="ml-1">{{ getByStatus(col.id).length }}</VChip>
              </VCardTitle>
            </VCardItem>
            <VDivider />
            <VCardText class="pa-2">
              <div class="d-flex flex-column gap-2">

                <!-- Tarjeta de riesgo -->
                <VCard v-for="risk in getByStatus(col.id)" :key="risk.id" variant="outlined" class="risk-card"
                  :class="{ 'risk-dragging': draggedRisk?.id === risk.id }" draggable="true"
                  @dragstart="onDragStart($event, risk)" @dragend="onDragEnd">
                  <VCardText class="pa-3">

                    <!-- Título -->
                    <div class="d-flex align-center gap-1 mb-2" style="min-width: 0;">
                      <VIcon size="14" :color="col.color">ri-error-warning-line</VIcon>
                      <span class="text-body-2 font-weight-medium"
                        style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" :title="risk.title">{{
                        risk.title }}</span>
                    </div>

                    <!-- Impacto + Probabilidad -->
                    <div class="d-flex align-center gap-1 flex-wrap mb-2">
                      <VChip :color="IMPACT_COLORS[risk.impact]" size="x-small" variant="flat">
                        {{ IMPACT_LABELS[risk.impact] }}
                      </VChip>
                      <VChip :color="PROB_COLORS[risk.probability]" size="x-small" variant="tonal">
                        {{ PROB_LABELS[risk.probability] }}
                      </VChip>
                    </div>

                    <!-- Acciones -->
                    <div class="d-flex justify-space-between align-center">
                      <VBtn size="x-small" variant="text" :color="col.color"
                        :to="{ name: 'risks-id', params: { projectId: risk.project_id, id: risk.id } }"
                        v-if="canAction('Riesgo.Update')">
                        <VIcon size="14">ri-pencil-line</VIcon>
                      </VBtn>

                      <VMenu v-if="canAction('Riesgo.Update')">
                        <template #activator="{ props: mp }">
                          <VBtn size="x-small" variant="text" v-bind="mp" @click.stop>
                            <VIcon size="14">ri-more-fill</VIcon>
                          </VBtn>
                        </template>
                        <VList density="compact">
                          <VListItem v-for="c in KANBAN_COLUMNS.filter(c => c.id !== col.id)" :key="c.id"
                            @click="moveRisk(risk, c.id)">
                            <template #prepend>
                              <VIcon size="14" :color="c.color">{{ c.icon }}</VIcon>
                            </template>
                            <VListItemTitle>Mover a {{ c.title }}</VListItemTitle>
                          </VListItem>
                        </VList>
                      </VMenu>
                    </div>

                  </VCardText>
                </VCard>

                <!-- Zona de drop vacía -->
                <div v-if="getByStatus(col.id).length === 0" class="empty-drop-zone"
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
      <VCard title="Eliminar Riesgo">
        <VCardText>¿Eliminar este riesgo?</VCardText>
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

.risk-card {
  cursor: grab;
  transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
}

.risk-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.risk-card:active {
  cursor: grabbing;
}

.risk-dragging {
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
