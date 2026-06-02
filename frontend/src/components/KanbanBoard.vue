<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import type { TaskI } from '@/interfaces/TaskI';
import { formatDate } from '@/utils/util';
import * as tasksService from '@/services/project-tasks.service';
import { useAppStore } from '@/store/useAppStore';
import { storeToRefs } from 'pinia';

const props = defineProps<{
  tasks: TaskI[];
}>();

const emit = defineEmits<{
  refresh: [];
}>();

const router = useRouter();
const appStore = useAppStore();
const { snackbar } = storeToRefs(appStore);

const columns = [
  { id: 'pending',     title: 'Pendientes',   color: 'warning', icon: 'mdi-clock-outline' },
  { id: 'in_progress', title: 'En Progreso',  color: 'info',    icon: 'mdi-progress-clock' },
  { id: 'review',      title: 'En Revisión',  color: 'primary', icon: 'mdi-account-check' },
  { id: 'completed',   title: 'Completadas',  color: 'success', icon: 'mdi-check-circle' },
  { id: 'cancelled',   title: 'Canceladas',   color: 'error',   icon: 'mdi-cancel' },
];

// ── Drag & Drop state ────────────────────────────────────────────────────────

/** Id de la columna que actualmente tiene el cursor encima durante un drag */
const dragOverColumn = ref<string | null>(null);
/** Tarea siendo arrastrada */
const draggedTask = ref<TaskI | null>(null);

function onDragStart(event: DragEvent, task: TaskI) {
  draggedTask.value = task;
  // Payload mínimo exigido por la spec (necesario en Firefox)
  event.dataTransfer?.setData('text/plain', String(task.id));
  if (event.dataTransfer) event.dataTransfer.effectAllowed = 'move';
}

function onDragEnd() {
  draggedTask.value = null;
  dragOverColumn.value = null;
}

function onDragEnter(columnId: string) {
  dragOverColumn.value = columnId;
}

function onDragLeave(event: DragEvent, columnId: string) {
  // Solo limpiar si el cursor sale del contenedor de la columna
  // (evita parpadeos al pasar sobre tarjetas hijas)
  const related = event.relatedTarget as HTMLElement | null;
  const column  = (event.currentTarget as HTMLElement);
  if (!column.contains(related)) {
    if (dragOverColumn.value === columnId) dragOverColumn.value = null;
  }
}

async function onDrop(event: DragEvent, targetColumnId: string) {
  event.preventDefault();
  dragOverColumn.value = null;

  if (!draggedTask.value || draggedTask.value.status === targetColumnId) {
    draggedTask.value = null;
    return;
  }

  const task = draggedTask.value;
  draggedTask.value = null;

  const response = await tasksService.update(task.project_id, task.id, {
    ...task,
    status: targetColumnId as any,
  });

  if (response.status) {
    snackbar.value = { show: true, text: 'Estado actualizado', color: 'success' };
    emit('refresh');
  } else {
    snackbar.value = { show: true, text: 'Error al actualizar', color: 'error' };
  }
}

// ── Helpers ──────────────────────────────────────────────────────────────────

const getTasksByStatus = (status: string) =>
  props.tasks.filter(task => task.status === status);

const getPriorityColor = (priority: string) => {
  const colors: Record<string, string> = {
    low: 'success', medium: 'info', high: 'warning', urgent: 'error',
  };
  return colors[priority?.toLowerCase()] || 'secondary';
};

const getProgressColor = (progress: number, dueDate: string | null) => {
  if (progress === 100) return 'success';
  if (dueDate && new Date(dueDate) < new Date()) return 'error';
  if (progress >= 70) return 'success';
  if (progress >= 40) return 'warning';
  return 'info';
};

const updateTaskStatus = async (task: TaskI, newStatus: string) => {
  const response = await tasksService.update(task.project_id, task.id, {
    ...task,
    status: newStatus as any,
  });
  if (response.status) {
    snackbar.value = { show: true, text: 'Estado actualizado', color: 'success' };
    emit('refresh');
  } else {
    snackbar.value = { show: true, text: 'Error al actualizar', color: 'error' };
  }
};

const goToTask = (taskId: number) => {
  router.push({ name: 'tasks-id', params: { projectId: taskId } });
};
</script>

<template>
  <div class="kanban-board">
    <VRow class="flex-nowrap overflow-x-auto pb-4" style="flex-wrap: nowrap;">
      <VCol
        v-for="column in columns"
        :key="column.id"
        cols="12" md="3"
        class="kanban-column"
        @dragover.prevent
        @dragenter.prevent="onDragEnter(column.id)"
        @dragleave="onDragLeave($event, column.id)"
        @drop="onDrop($event, column.id)"
      >
        <VCard
          :color="column.color"
          variant="tonal"
          class="h-100"
          :class="{ 'drop-target': dragOverColumn === column.id }"
        >
          <VCardItem>
            <VCardTitle class="d-flex align-center gap-2">
              <VIcon :icon="column.icon" :color="column.color" />
              <span>{{ column.title }}</span>
              <VChip size="small" color="grey">{{ getTasksByStatus(column.id).length }}</VChip>
            </VCardTitle>
          </VCardItem>

          <VDivider />

          <VCardText class="pa-2">
            <div class="d-flex flex-column gap-2">
              <VCard
                v-for="task in getTasksByStatus(column.id)"
                :key="task.id"
                variant="outlined"
                class="task-card cursor-pointer"
                :class="{ 'task-dragging': draggedTask?.id === task.id }"
                draggable="true"
                @dragstart="onDragStart($event, task)"
                @dragend="onDragEnd"
                @click="goToTask(task.id)"
              >
                <VCardText class="pa-3">
                  <div class="d-flex justify-space-between align-start mb-2">
                    <strong class="text-body-1">{{ task.title }}</strong>
                    <VChip :color="getPriorityColor(task.priority!)" size="x-small" variant="flat">
                      {{ task.priority }}
                    </VChip>
                  </div>

                  <div class="mb-2">
                    <div class="d-flex align-center gap-2">
                      <VProgressLinear
                        :model-value="task.progress || 0"
                        :color="getProgressColor(task.progress || 0, task.due_date!)"
                        height="4"
                        rounded
                        class="flex-grow-1"
                      />
                      <span class="text-caption">{{ task.progress || 0 }}%</span>
                    </div>
                  </div>

                  <div class="d-flex justify-space-between align-center text-caption">
                    <div class="d-flex align-center gap-1">
                      <VIcon size="small" color="grey">mdi-calendar</VIcon>
                      <span :class="{ 'text-error': new Date(task.due_date!) < new Date() && task.progress !== 100 }">
                        {{ formatDate(task.due_date!) ?? '—' }}
                      </span>
                    </div>

                    <VMenu v-if="column.id !== 'completed' && column.id !== 'cancelled'">
                      <template #activator="{ props: menuProps }">
                        <VBtn size="x-small" variant="text" v-bind="menuProps" @click.stop>
                          <VIcon size="small">mdi-dots-vertical</VIcon>
                        </VBtn>
                      </template>
                      <VList density="compact">
                        <VListItem
                          v-for="col in columns.filter(c => c.id !== column.id)"
                          :key="col.id"
                          @click="updateTaskStatus(task, col.id)"
                        >
                          <VListItemTitle>Mover a {{ col.title }}</VListItemTitle>
                        </VListItem>
                      </VList>
                    </VMenu>
                  </div>
                </VCardText>
              </VCard>

              <!-- Zona de drop vacía cuando la columna no tiene tarjetas -->
              <div
                v-if="getTasksByStatus(column.id).length === 0"
                class="empty-drop-zone"
                :class="{ 'empty-drop-zone--active': dragOverColumn === column.id }"
              >
                <VIcon size="32" color="grey-lighten-1">mdi-tray-arrow-down</VIcon>
                <span class="text-caption text-grey">Arrastra aquí</span>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>

<style scoped>
.kanban-board {
  overflow-x: auto;
}

.kanban-column {
  min-width: 320px;
}

/* Columna resaltada cuando se arrastra una tarjeta sobre ella */
.drop-target {
  outline: 2px dashed currentColor;
  outline-offset: -4px;
  opacity: 0.9;
}

/* Tarjeta siendo arrastrada */
.task-card {
  transition:
    transform 0.15s ease,
    box-shadow 0.15s ease,
    opacity 0.15s ease;
  cursor: grab;
}

.task-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.task-card:active {
  cursor: grabbing;
}

.task-dragging {
  opacity: 0.4;
}

/* Zona vacía de drop */
.empty-drop-zone {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 80px;
  border: 2px dashed rgba(0, 0, 0, 0.15);
  border-radius: 8px;
  padding: 16px;
  transition: border-color 0.2s ease, background-color 0.2s ease;
}

.empty-drop-zone--active {
  border-color: currentColor;
  background-color: rgba(0, 0, 0, 0.04);
}

.cursor-pointer {
  cursor: pointer;
}

.gap-2 {
  gap: 8px;
}
</style>
