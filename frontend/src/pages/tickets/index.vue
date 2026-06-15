<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { canAction } from '@/helpers/canAction';
import { useEnsureCurrentProject } from '@/composables/useEnsureCurrentProject';
import * as ticketsService from '@/services/tickets.service';
import type { TicketI } from '@/interfaces/TicketI';
import type { PaginacionYQueryI } from '@/interfaces/PaginacionScoutI';

const route = useRoute();
const router = useRouter();
const appStore = useAppStore();
const { loader, snackbar } = storeToRefs(appStore);

useEnsureCurrentProject();

const projectId = () => Number(route.params.projectId);

// ── Vista ─────────────────────────────────────────────────────────────────────

const viewMode = ref<'list' | 'kanban'>('list');

// ── Datos ─────────────────────────────────────────────────────────────────────

const isDialogVisible = ref<boolean>(false);
const paginacionYquery = ref<PaginacionYQueryI>({ page: 1, query: '', last_page: 1 });
const data = ref<TicketI[]>([]);

const handleGetData = async () => {
  loader.value = true;
  const response = await ticketsService.index(projectId(), {
    page: paginacionYquery.value.page,
    query: paginacionYquery.value.query,
  });
  if (response.status && response.items) {
    data.value = (response.items as any).data ?? response.items;
    paginacionYquery.value.last_page = (response.items as any).last_page ?? 1;
  }
  loader.value = false;
};

const itemDestroy = ref<TicketI | null>(null);

const handleDestroy = async () => {
  if (!itemDestroy.value) return;
  loader.value = true;
  const response = await ticketsService.destroy(itemDestroy.value.project_id, itemDestroy.value.id);
  if (response.status) {
    snackbar.value = { show: true, text: 'Ticket eliminado', color: 'success' };
    handleGetData();
  }
  loader.value = false;
  isDialogVisible.value = false;
};

// ── Kanban ────────────────────────────────────────────────────────────────────

const KANBAN_COLUMNS = [
  { id: 'open', title: 'Abierto', color: 'info', icon: 'ri-coupon-line' },
  { id: 'in_progress', title: 'En Progreso', color: 'warning', icon: 'ri-progress-5-line' },
  { id: 'resolved', title: 'Resuelto', color: 'success', icon: 'ri-checkbox-circle-line' },
  { id: 'closed', title: 'Cerrado', color: 'secondary', icon: 'ri-lock-line' },
];

const getByStatus = (status: string) => data.value.filter(t => t.status === status);

// DnD
const draggedTicket = ref<TicketI | null>(null);
const dragOverColumn = ref<string | null>(null);

function onDragStart(e: DragEvent, ticket: TicketI) {
  draggedTicket.value = ticket;
  e.dataTransfer?.setData('text/plain', String(ticket.id));
  if (e.dataTransfer) e.dataTransfer.effectAllowed = 'move';
}

function onDragEnd() {
  draggedTicket.value = null;
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
  if (!draggedTicket.value || draggedTicket.value.status === targetColId) {
    draggedTicket.value = null;
    return;
  }
  const ticket = draggedTicket.value;
  draggedTicket.value = null;
  const response = await ticketsService.update(ticket.project_id, ticket.id, {
    ...ticket,
    status: targetColId as any,
  });
  if (response.status) {
    snackbar.value = { show: true, text: 'Estado actualizado', color: 'success' };
    handleGetData();
  } else {
    snackbar.value = { show: true, text: 'Error al actualizar', color: 'error' };
  }
}

async function moveTicket(ticket: TicketI, newStatus: string) {
  const response = await ticketsService.update(ticket.project_id, ticket.id, {
    ...ticket,
    status: newStatus as any,
  });
  if (response.status) {
    snackbar.value = { show: true, text: 'Estado actualizado', color: 'success' };
    handleGetData();
  } else {
    snackbar.value = { show: true, text: 'Error al actualizar', color: 'error' };
  }
}

// ── Helpers de color / etiquetas ─────────────────────────────────────────────

const STATUS_COLORS: Record<string, string> = {
  open: 'info', in_progress: 'warning', resolved: 'success', closed: 'secondary',
};

const STATUS_LABELS: Record<string, string> = {
  open: 'Abierto', in_progress: 'En Progreso', resolved: 'Resuelto', closed: 'Cerrado',
};

const PRIORITY_COLORS: Record<string, string> = {
  low: 'success', medium: 'info', high: 'warning', critical: 'error',
};

const PRIORITY_LABELS: Record<string, string> = {
  low: 'Baja', medium: 'Media', high: 'Alta', critical: 'Crítica',
};

const avatarInitials = (name?: string) =>
  name ? name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase() : '?';

watch(() => isDialogVisible.value, v => { if (!v) itemDestroy.value = null; });
onMounted(handleGetData);
const viewOptions = [
  { title: '📋 Lista', value: 'list' },
  { title: '🎯 Kanban', value: 'kanban' },
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
              <h4 class="text-h4 text-wrap">Listado de <strong>Tickets</strong></h4>
              <div class="d-flex align-center gap-3">
                <VSelect v-model="viewMode" :items="viewOptions" item-title="title" item-value="value" density="compact"
                  variant="solo" flat hide-details class="view-selector" style="max-width: 130px;" />
                <VBtn variant="flat" prepend-icon="ri-add-line"
                  :to="{ name: 'tickets-new', params: { projectId: projectId() } }" v-if="canAction('ticket.create')">
                  Nuevo ticket
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
                <th class="text-uppercase">Asunto</th>
                <th class="text-uppercase">Estado</th>
                <th class="text-uppercase">Prioridad</th>
                <th class="text-uppercase">Asignado a</th>
                <th class="text-uppercase">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in data" :key="item.id">
                <td>
                  <div class="d-flex align-center gap-2">
                    <VIcon size="16" :color="STATUS_COLORS[item.status]">ri-coupon-line</VIcon>
                    <span class="font-weight-medium">{{ item.subject }}</span>
                  </div>
                </td>
                <td>
                  <VChip :color="STATUS_COLORS[item.status]" size="small" variant="flat">
                    {{ STATUS_LABELS[item.status] ?? item.status }}
                  </VChip>
                </td>
                <td>
                  <VChip :color="PRIORITY_COLORS[item.priority]" size="small" variant="tonal">
                    {{ PRIORITY_LABELS[item.priority] ?? item.priority }}
                  </VChip>
                </td>
                <td>
                  <div v-if="item.assignee" class="d-flex align-center gap-2">
                    <VAvatar size="28" color="primary" variant="tonal">
                      <span class="text-caption font-weight-bold">
                        {{ avatarInitials(item.assignee.name) }}
                      </span>
                    </VAvatar>
                    <span class="text-body-2">{{ item.assignee.name }}</span>
                  </div>
                  <span v-else class="text-caption text-disabled">Sin asignar</span>
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <VBtn icon size="small" variant="text"
                      :to="{ name: 'tickets-view', params: { projectId: projectId(), id: item.id } }">
                      <VIcon icon="ri-eye-line" color="primary" size="small" />
                    </VBtn>
                    <VBtn icon size="small" variant="text"
                      :to="{ name: 'tickets-id', params: { projectId: projectId(), id: item.id } }"
                      v-if="canAction(['ticket.edit-any', 'ticket.edit-own'], item.created_by)">
                      <VIcon icon="ri-pencil-line" color="warning" size="small" />
                    </VBtn>
                    <VBtn icon size="small" variant="text" v-if="canAction('ticket.delete')"
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

    <!-- ── Paginación (lista) ─────────────────────────────────────────────── -->
    <VPagination v-if="viewMode === 'list'" class="mt-4 mr-3" color="primary" v-model="paginacionYquery.page"
      :total-visible="7" :length="paginacionYquery.last_page" style="margin-left: auto;"
      @update:model-value="handleGetData" />

    <!-- ── Vista Kanban ───────────────────────────────────────────────────── -->
    <VCol cols="12" v-if="viewMode === 'kanban'">
      <div class="kanban-board">
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

                  <!-- Tarjeta de ticket -->
                  <VCard v-for="ticket in getByStatus(col.id)" :key="ticket.id" variant="outlined" class="ticket-card"
                    :class="{ 'ticket-dragging': draggedTicket?.id === ticket.id }" draggable="true"
                    @dragstart="onDragStart($event, ticket)" @dragend="onDragEnd">
                    <VCardText class="pa-3">

                      <!-- Asunto + prioridad -->
                      <div class="d-flex justify-space-between align-start mb-2 gap-2">
                        <div class="d-flex align-center gap-1" style="min-width: 0;">
                          <VIcon size="14" :color="col.color">ri-coupon-line</VIcon>
                          <span class="text-body-2 font-weight-medium"
                            style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                            :title="ticket.subject">{{ ticket.subject }}</span>
                        </div>
                        <VChip :color="PRIORITY_COLORS[ticket.priority]" size="x-small" variant="flat"
                          class="flex-shrink-0">
                          {{ PRIORITY_LABELS[ticket.priority] ?? ticket.priority }}
                        </VChip>
                      </div>

                      <!-- Asignado a -->
                      <div class="d-flex align-center gap-2 mb-2">
                        <template v-if="ticket.assignee">
                          <VAvatar size="22" color="primary" variant="tonal">
                            <span style="font-size: 0.6rem; font-weight: 700;">
                              {{ avatarInitials(ticket.assignee.name) }}
                            </span>
                          </VAvatar>
                          <span class="text-caption text-medium-emphasis">{{ ticket.assignee.name }}</span>
                        </template>
                        <span v-else class="text-caption text-disabled">Sin asignar</span>
                      </div>

                      <!-- Acciones -->
                      <div class="d-flex justify-space-between align-center">
                        <VBtn size="x-small" variant="text" :color="col.color"
                          :to="{ name: 'tickets-id', params: { projectId: ticket.project_id, id: ticket.id } }"
                          v-if="canAction(['ticket.edit-any', 'ticket.edit-own'], ticket.created_by)">
                          <VIcon size="14">ri-pencil-line</VIcon>
                        </VBtn>

                        <VMenu v-if="canAction(['ticket.edit-any', 'ticket.edit-own'], ticket.created_by)">
                          <template #activator="{ props: mp }">
                            <VBtn size="x-small" variant="text" v-bind="mp" @click.stop>
                              <VIcon size="14">ri-more-fill</VIcon>
                            </VBtn>
                          </template>
                          <VList density="compact">
                            <VListItem v-for="c in KANBAN_COLUMNS.filter(c => c.id !== col.id)" :key="c.id"
                              @click="moveTicket(ticket, c.id)">
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
      </div>
    </VCol>

    <!-- ── Diálogo eliminar ───────────────────────────────────────────────── -->
    <VDialog v-model="isDialogVisible" persistent class="v-dialog-sm">
      <VCard title="Eliminar Ticket">
        <VCardText>¿Eliminar este ticket?</VCardText>
        <VCardText class="d-flex justify-end flex-wrap gap-4">
          <VBtn variant="outlined" @click="isDialogVisible = false">Cancelar</VBtn>
          <VBtn color="error" @click="handleDestroy">Eliminar</VBtn>
        </VCardText>
      </VCard>
    </VDialog>

  </VRow>
</template>

<style scoped>
.kanban-board {
  overflow-x: auto;
}

.kanban-drop-target {
  outline: 2px dashed currentColor;
  outline-offset: -4px;
}

.ticket-card {
  cursor: grab;
  transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
}

.ticket-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.ticket-card:active {
  cursor: grabbing;
}

.ticket-dragging {
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
