<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from 'vue';
import type { TaskI } from '@/interfaces/TaskI';

const props = defineProps<{ tasks: TaskI[] }>();

// ── Zoom ──────────────────────────────────────────────────────────────────────
// cellWidth controla cuántos px ocupa un día. Rango continuo 6–60 px.

const MIN_CELL = 6;
const MAX_CELL = 60;
const cellWidth = ref(22); // equivale al preset "semana"

const zoomPercent = computed({
  get: () => Math.round(((cellWidth.value - MIN_CELL) / (MAX_CELL - MIN_CELL)) * 100),
  set: (v: number) => {
    cellWidth.value = Math.round(MIN_CELL + (v / 100) * (MAX_CELL - MIN_CELL));
  },
});

const zoomLabel = computed(() => {
  if (cellWidth.value >= 36) return 'Día';
  if (cellWidth.value >= 16) return 'Semana';
  return 'Mes';
});

function setZoomPreset(preset: 'day' | 'week' | 'month') {
  cellWidth.value = { day: 44, week: 22, month: 10 }[preset];
}

function onWheelZoom(e: WheelEvent) {
  if (!e.ctrlKey && !e.metaKey) return;
  e.preventDefault();
  const delta = e.deltaY > 0 ? -2 : 2;
  cellWidth.value = Math.min(MAX_CELL, Math.max(MIN_CELL, cellWidth.value + delta));
}

// Listener no-passive para poder llamar preventDefault en Ctrl+scroll
const ganttScrollEl = ref<HTMLElement | null>(null);

onMounted(() => {
  ganttScrollEl.value?.addEventListener('wheel', onWheelZoom, { passive: false });
});
onUnmounted(() => {
  ganttScrollEl.value?.removeEventListener('wheel', onWheelZoom);
});

// ── Task data ─────────────────────────────────────────────────────────────────

const ganttTasks = computed(() =>
  props.tasks
    .filter(t => t.due_date)
    .map(t => {
      const start = new Date(t.created_at!);
      start.setHours(0, 0, 0, 0);
      const end = new Date(t.due_date!);
      end.setHours(0, 0, 0, 0);
      const duration = Math.max(1, Math.ceil((end.getTime() - start.getTime()) / 86_400_000));
      return { ...t, startDate: start, endDate: end, duration };
    })
    .sort((a, b) => a.startDate.getTime() - b.startDate.getTime()),
);

// ── Date range ────────────────────────────────────────────────────────────────

function floorDay(d: Date): Date {
  const r = new Date(d);
  r.setHours(0, 0, 0, 0);
  return r;
}

const minDate = computed(() => {
  if (!ganttTasks.value.length) return floorDay(new Date());
  const d = new Date(Math.min(...ganttTasks.value.map(t => t.startDate.getTime())));
  d.setDate(d.getDate() - 1);
  return d;
});

const maxDate = computed(() => {
  if (!ganttTasks.value.length) return floorDay(new Date());
  const d = new Date(Math.max(...ganttTasks.value.map(t => t.endDate.getTime())));
  d.setDate(d.getDate() + 2);
  return d;
});

const daysArray = computed<Date[]>(() => {
  const days: Date[] = [];
  const cur = new Date(minDate.value);
  while (cur <= maxDate.value) {
    days.push(new Date(cur));
    cur.setDate(cur.getDate() + 1);
  }
  return days;
});

const timelineWidth = computed(() => daysArray.value.length * cellWidth.value);

// ── Month groups (fila superior del encabezado) ───────────────────────────────

const monthGroups = computed(() => {
  const groups: { label: string; days: number }[] = [];
  let curKey = '';
  for (const day of daysArray.value) {
    const key = `${day.getFullYear()}-${day.getMonth()}`;
    const label = day.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });
    if (key !== curKey) { groups.push({ label, days: 1 }); curKey = key; }
    else groups[groups.length - 1].days++;
  }
  return groups;
});

// ── Today ─────────────────────────────────────────────────────────────────────

const todayOffset = computed(() => {
  const t = floorDay(new Date());
  const diff = Math.ceil((t.getTime() - minDate.value.getTime()) / 86_400_000);
  if (diff < 0 || diff >= daysArray.value.length) return null;
  return diff * cellWidth.value + cellWidth.value / 2;
});

// ── Helpers ───────────────────────────────────────────────────────────────────

const isWeekend = (d: Date) => d.getDay() === 0 || d.getDay() === 6;
const isToday   = (d: Date) => d.toDateString() === new Date().toDateString();

/** Muestra el número de día según el nivel de zoom para no saturar el encabezado. */
const showDayLabel = (d: Date): boolean => {
  if (cellWidth.value >= 36) return true;                       // 'day'
  if (cellWidth.value >= 16) return d.getDay() === 1;          // 'week': solo lunes
  return d.getDate() === 1 || d.getDate() === 15;              // 'month'
};

/** Posición izquierda de la barra en px. */
const getBarLeft = (t: { startDate: Date }) =>
  Math.ceil((t.startDate.getTime() - minDate.value.getTime()) / 86_400_000) * cellWidth.value;

/** Ancho de la barra en px (mínimo 1 celda). */
const getBarWidth = (t: { duration: number }) =>
  Math.max(cellWidth.value, t.duration * cellWidth.value);

const isOverdue = (t: TaskI) =>
  !!t.due_date && new Date(t.due_date) < new Date() && (t.progress ?? 0) < 100;

// ── Colores y etiquetas ───────────────────────────────────────────────────────

const STATUS_COLORS: Record<string, string> = {
  pending:     '#D97706',
  in_progress: '#2563EB',
  review:      '#7C3AED',
  completed:   '#16A34A',
  cancelled:   '#6B7280',
};

const STATUS_LABELS: Record<string, string> = {
  pending:     'Pendiente',
  in_progress: 'En progreso',
  review:      'En revisión',
  completed:   'Completada',
  cancelled:   'Cancelada',
};

const PRIORITY_LABELS: Record<string, string> = {
  low: 'Baja', medium: 'Media', high: 'Alta', urgent: 'Urgente',
};

const getPriorityColor = (p: string) =>
  ({ low: 'success', medium: 'info', high: 'warning', urgent: 'error' }[p?.toLowerCase()] ?? 'secondary');

// ── Filtros de categoría ─────────────────────────────────────────────────────
// 'overdue' es una categoría virtual calculada, no un status de la API.

type FilterKey = keyof typeof STATUS_COLORS | 'overdue';

const activeFilters = ref<Set<FilterKey>>(new Set(
  [...Object.keys(STATUS_COLORS), 'overdue'] as FilterKey[],
));

function toggleFilter(key: FilterKey) {
  const all = [...Object.keys(STATUS_COLORS), 'overdue'] as FilterKey[];
  if (activeFilters.value.size === all.length) {
    // Si estaban todos activos, seleccionar solo el clickeado
    activeFilters.value = new Set([key]);
    return;
  }
  if (activeFilters.value.has(key) && activeFilters.value.size === 1) {
    // Si solo quedaba este, restaurar todos
    activeFilters.value = new Set(all);
    return;
  }
  const next = new Set(activeFilters.value);
  next.has(key) ? next.delete(key) : next.add(key);
  activeFilters.value = next;
}

const allSelected = computed(() =>
  activeFilters.value.size === Object.keys(STATUS_COLORS).length + 1,
);

function selectAll() {
  activeFilters.value = new Set(
    [...Object.keys(STATUS_COLORS), 'overdue'] as FilterKey[],
  );
}

const visibleTasks = computed(() =>
  ganttTasks.value.filter(t => {
    if (isOverdue(t) && activeFilters.value.has('overdue')) return true;
    if (!isOverdue(t) && activeFilters.value.has(t.status as FilterKey)) return true;
    return false;
  }),
);

/** Color de la barra: rojo si está atrasada, color de estado en caso contrario. */
const getBarColor = (t: TaskI) =>
  isOverdue(t) ? '#DC2626' : (STATUS_COLORS[t.status] ?? '#9E9E9E');

/** Texto del tooltip nativo al hacer hover sobre la barra. */
const getTooltip = (task: any): string =>
  [
    task.title,
    `Estado: ${STATUS_LABELS[task.status] ?? task.status}`,
    `Prioridad: ${PRIORITY_LABELS[task.priority] ?? task.priority}`,
    `Progreso: ${task.progress ?? 0}%`,
    `Inicio: ${task.startDate.toLocaleDateString('es-ES')}`,
    `Fin: ${task.endDate.toLocaleDateString('es-ES')}`,
    `Duración: ${task.duration} día${task.duration !== 1 ? 's' : ''}`,
  ].join('\n');
</script>

<template>
  <VCard>

    <!-- ── Encabezado ──────────────────────────────────────────────────────── -->
    <VCardItem>
      <div class="d-flex justify-space-between align-center flex-wrap gap-3">
        <div>
          <VCardTitle>Diagrama de Gantt</VCardTitle>
          <VCardSubtitle v-if="ganttTasks.length">
            {{ ganttTasks.length }} tarea{{ ganttTasks.length !== 1 ? 's' : '' }} ·
            {{ minDate.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' }) }}
            –
            {{ maxDate.toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' }) }}
          </VCardSubtitle>
        </div>
        <div class="d-flex align-center gap-3">
          <!-- Presets rápidos -->
          <VBtnToggle :model-value="zoomLabel" divided density="compact">
            <VBtn value="Día"    size="small" @click="setZoomPreset('day')">Día</VBtn>
            <VBtn value="Semana" size="small" @click="setZoomPreset('week')">Semana</VBtn>
            <VBtn value="Mes"    size="small" @click="setZoomPreset('month')">Mes</VBtn>
          </VBtnToggle>
          <!-- Slider fino -->
          <div class="d-flex align-center gap-1" style="width: 160px;">
            <VIcon size="16" color="grey">ri-search-line-minus-outline</VIcon>
            <VSlider
              v-model="zoomPercent"
              :min="0"
              :max="100"
              :step="1"
              hide-details
              density="compact"
              thumb-size="14"
              track-size="3"
              color="primary"
              style="flex: 1;"
            />
            <VIcon size="16" color="grey">ri-search-line-plus-outline</VIcon>
          </div>
          <VChip size="x-small" variant="tonal" color="primary" label>{{ cellWidth }}px/día</VChip>
        </div>
      </div>
    </VCardItem>

    <!-- ── Leyenda / Filtros ───────────────────────────────────────────────── -->
    <div class="gantt-legend px-4 pb-3" style="gap: 8px;">

      <!-- Botón «Todas» -->
      <VChip
        size="small"
        :variant="allSelected ? 'flat' : 'outlined'"
        :color="allSelected ? 'primary' : 'default'"
        class="gantt-filter-chip"
        @click="selectAll"
      >
        <template #prepend>
          <VIcon size="12" class="mr-1">{{ allSelected ? 'ri-check-double-line' : 'ri-filter-line' }}</VIcon>
        </template>
        Todas
      </VChip>

      <!-- Chips de estado -->
      <VChip
        v-for="(color, key) in STATUS_COLORS"
        :key="key"
        size="small"
        :variant="activeFilters.has(key as FilterKey) && !allSelected ? 'flat' : 'tonal'"
        :style="{
          borderColor: color,
          '--chip-color': color,
          opacity: !allSelected && !activeFilters.has(key as FilterKey) ? 0.4 : 1,
        }"
        class="gantt-filter-chip"
        @click="toggleFilter(key as FilterKey)"
      >
        <template #prepend>
          <span
            class="gantt-legend-dot mr-1"
            :style="{ background: color, opacity: activeFilters.has(key as FilterKey) ? 1 : 0.35 }"
          ></span>
        </template>
        {{ STATUS_LABELS[key] }}
        <VIcon
          v-if="activeFilters.has(key as FilterKey) && !allSelected"
          size="12"
          class="ml-1"
        >ri-check-line</VIcon>
      </VChip>

      <!-- Chip Atrasada -->
      <VChip
        size="small"
        :variant="activeFilters.has('overdue') && !allSelected ? 'flat' : 'tonal'"
        :style="{
          borderColor: '#DC2626',
          opacity: !allSelected && !activeFilters.has('overdue') ? 0.4 : 1,
        }"
        class="gantt-filter-chip"
        @click="toggleFilter('overdue')"
      >
        <template #prepend>
          <span
            class="gantt-legend-dot mr-1"
            :style="{ background: '#DC2626', opacity: activeFilters.has('overdue') ? 1 : 0.35 }"
          ></span>
        </template>
        Atrasada
        <VIcon v-if="activeFilters.has('overdue') && !allSelected" size="12" class="ml-1">ri-check-line</VIcon>
      </VChip>

      <!-- Separador + Hoy (no filtrable) -->
      <span class="gantt-legend-item ml-2">
        <span class="gantt-legend-today-line"></span>
        <span class="text-caption text-medium-emphasis">Hoy</span>
      </span>

      <!-- Contador -->
      <span class="text-caption text-disabled ml-auto">
        {{ visibleTasks.length }} / {{ ganttTasks.length }} tareas
      </span>
    </div>

    <VDivider />

    <VCardText class="pa-0">

      <!-- ── Estado vacío ──────────────────────────────────────────────────── -->
      <div v-if="ganttTasks.length === 0" class="text-center pa-12">
        <VIcon icon="ri-bar-chart-horizontal-line" size="72" color="grey-lighten-1" />
        <p class="text-h6 mt-4 text-medium-emphasis">Sin tareas con fechas asignadas</p>
        <p class="text-body-2 text-disabled mt-1">
          Asigna fechas de vencimiento a las tareas para visualizarlas aquí
        </p>
      </div>

      <!-- ── Gantt ─────────────────────────────────────────────────────────── -->
      <div v-else class="gantt-scroll" ref="ganttScrollEl">
        <!-- Hint Ctrl+scroll -->
        <div class="gantt-zoom-hint text-caption text-disabled">Ctrl + rueda para hacer zoom</div>

        <!-- Fila: meses -->
        <div class="gantt-row">
          <div class="gantt-label gantt-header-cell gantt-month-header">
            <span class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Tarea</span>
          </div>
          <div class="gantt-timeline" :style="{ width: `${timelineWidth}px` }">
            <div class="d-flex h-100">
              <div
                v-for="g in monthGroups"
                :key="g.label"
                class="gantt-month-cell"
                :style="{ width: `${g.days * cellWidth}px` }"
              >{{ g.label }}</div>
            </div>
          </div>
        </div>

        <!-- Fila: días -->
        <div class="gantt-row gantt-day-header">
          <div class="gantt-label gantt-header-cell"></div>
          <div class="gantt-timeline" :style="{ width: `${timelineWidth}px` }">
            <div class="d-flex h-100">
              <div
                v-for="day in daysArray"
                :key="day.toISOString()"
                class="gantt-day-cell"
                :class="{ 'is-weekend': isWeekend(day), 'is-today': isToday(day) }"
                :style="{ width: `${cellWidth}px`, flexShrink: 0 }"
              >
                <span v-if="showDayLabel(day)">{{ day.getDate() }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Sin resultados por filtro -->
        <div v-if="visibleTasks.length === 0" class="text-center pa-8">
          <VIcon icon="ri-filter-off-line" size="48" color="grey-lighten-1" />
          <p class="text-body-2 text-disabled mt-2">Ninguna tarea coincide con los filtros seleccionados</p>
          <VBtn size="small" variant="text" color="primary" class="mt-1" @click="selectAll">Mostrar todas</VBtn>
        </div>

        <!-- Filas: tareas -->
        <div v-for="task in visibleTasks" :key="task.id" class="gantt-row gantt-task-row">

          <!-- Columna de etiqueta (sticky) -->
          <div class="gantt-label gantt-task-label">
            <div class="d-flex align-center gap-1" style="min-width: 0;">
              <VIcon
                size="14"
                :color="isOverdue(task) ? 'error' : task.progress === 100 ? 'success' : 'grey'"
              >
                {{
                  task.progress === 100
                    ? 'ri-checkbox-circle-fill'
                    : isOverdue(task)
                      ? 'ri-error-warning-fill'
                      : 'ri-checkbox-blank-circle-line'
                }}
              </VIcon>
              <span
                class="text-body-2 font-weight-medium"
                style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                :title="task.title"
              >{{ task.title }}</span>
            </div>
            <div class="d-flex align-center gap-1 mt-1">
              <VChip size="x-small" :color="getPriorityColor(task.priority!)" variant="tonal">
                {{ PRIORITY_LABELS[task.priority!] ?? task.priority }}
              </VChip>
              <span class="text-caption text-medium-emphasis">{{ task.progress ?? 0 }}%</span>
              <span v-if="isOverdue(task)" class="text-caption text-error font-weight-medium">· Atrasada</span>
            </div>
          </div>

          <!-- Columna de timeline con barra -->
          <div class="gantt-timeline gantt-bar-area" :style="{ width: `${timelineWidth}px` }">

            <!-- Grid de fondo -->
            <div class="gantt-grid-bg">
              <div
                v-for="(day, i) in daysArray"
                :key="i"
                class="gantt-grid-col"
                :class="{ 'is-weekend': isWeekend(day), 'is-today': isToday(day) }"
                :style="{ width: `${cellWidth}px` }"
              ></div>
            </div>

            <!-- Línea de hoy -->
            <div
              v-if="todayOffset !== null"
              class="gantt-today-line"
              :style="{ left: `${todayOffset}px` }"
            ></div>

            <!-- Barra de la tarea -->
            <div
              class="gantt-bar"
              :style="{ left: `${getBarLeft(task)}px`, width: `${getBarWidth(task)}px` }"
              :title="getTooltip(task)"
            >
              <!-- Fondo (duración total, baja opacidad) -->
              <div class="gantt-bar-track" :style="{ background: getBarColor(task) }"></div>
              <!-- Progreso completado -->
              <div
                class="gantt-bar-fill"
                :style="{ width: `${task.progress ?? 0}%`, background: getBarColor(task) }"
              ></div>
              <!-- Texto superpuesto -->
              <div class="gantt-bar-text">
                <VIcon v-if="isOverdue(task)" size="10" color="white">ri-alert-fill</VIcon>
                {{ task.progress ?? 0 }}%
              </div>
            </div>

          </div>
        </div>

      </div>
    </VCardText>
  </VCard>
</template>

<style scoped>
/* ── Contenedor con scroll ────────────────────────────────────────── */
.gantt-scroll {
  overflow-x: auto;
  overflow-y: auto;
  max-height: 580px;
}

/* ── Filas ────────────────────────────────────────────────────────── */
.gantt-row {
  display: flex;
  min-width: max-content;
  border-bottom: 1px solid rgba(var(--v-border-color), 0.08);
}

/* ── Columna de etiqueta (fija a la izquierda) ────────────────────── */
.gantt-label {
  width: 240px;
  min-width: 240px;
  flex-shrink: 0;
  position: sticky;
  left: 0;
  z-index: 3;
  background: rgb(var(--v-theme-surface));
  border-right: 1px solid rgba(var(--v-border-color), 0.12);
  padding: 0 12px;
}

/* ── Encabezados ──────────────────────────────────────────────────── */
.gantt-header-cell {
  height: 28px;
  display: flex;
  align-items: center;
}

.gantt-month-header {
  background: rgba(var(--v-theme-on-surface), 0.02);
}

.gantt-day-header {
  position: sticky;
  top: 0;
  z-index: 4;
}

.gantt-day-header .gantt-label {
  z-index: 5;
}

/* Celda de mes */
.gantt-month-cell {
  height: 28px;
  display: flex;
  align-items: center;
  padding: 0 8px;
  font-size: 0.72rem;
  font-weight: 600;
  text-transform: capitalize;
  color: rgba(var(--v-theme-on-surface), 0.6);
  border-right: 1px solid rgba(var(--v-border-color), 0.1);
  background: rgba(var(--v-theme-on-surface), 0.02);
  overflow: hidden;
  white-space: nowrap;
}

/* Celda de día */
.gantt-day-cell {
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.68rem;
  color: rgba(var(--v-theme-on-surface), 0.5);
  border-right: 1px solid rgba(var(--v-border-color), 0.06);
  background: rgba(var(--v-theme-on-surface), 0.01);
}

.gantt-day-cell.is-weekend {
  background: rgba(var(--v-theme-on-surface), 0.03);
  color: rgba(var(--v-theme-on-surface), 0.3);
}

.gantt-day-cell.is-today {
  background: rgba(var(--v-theme-primary), 0.14);
  color: rgb(var(--v-theme-primary));
  font-weight: 700;
}

/* ── Filas de tareas ──────────────────────────────────────────────── */
.gantt-task-row {
  min-height: 58px;
}

.gantt-task-label {
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 6px 12px;
}

/* ── Columna de timeline ──────────────────────────────────────────── */
.gantt-timeline {
  flex-shrink: 0;
  position: relative;
}

.gantt-bar-area {
  min-height: 58px;
}

/* ── Grid de fondo ────────────────────────────────────────────────── */
.gantt-grid-bg {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
}

.gantt-grid-col {
  height: 100%;
  flex-shrink: 0;
  border-right: 1px solid rgba(var(--v-border-color), 0.04);
}

.gantt-grid-col.is-weekend {
  background: rgba(var(--v-theme-on-surface), 0.025);
}

.gantt-grid-col.is-today {
  background: rgba(var(--v-theme-primary), 0.06);
}

/* ── Línea de hoy ─────────────────────────────────────────────────── */
.gantt-today-line {
  position: absolute;
  top: 0;
  bottom: 0;
  width: 2px;
  background: rgb(var(--v-theme-error));
  z-index: 2;
  pointer-events: none;
  border-radius: 1px;
}

.gantt-today-line::before {
  content: '';
  position: absolute;
  top: 4px;
  left: 50%;
  transform: translateX(-50%);
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: rgb(var(--v-theme-error));
}

/* ── Barra ────────────────────────────────────────────────────────── */
.gantt-bar {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  height: 28px;
  border-radius: 6px;
  overflow: hidden;
  cursor: pointer;
  transition: filter 0.15s ease, transform 0.15s ease;
  min-width: 8px;
}

.gantt-bar:hover {
  filter: brightness(1.12);
  transform: translateY(-50%) scaleY(1.1);
  z-index: 1;
}

/* Fondo tenue (duración completa) */
.gantt-bar-track {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  border-radius: 6px;
  opacity: 0.18;
}

/* Progreso (sobre el fondo) */
.gantt-bar-fill {
  position: absolute;
  top: 0;
  left: 0;
  height: 100%;
  border-radius: 6px;
  transition: width 0.4s ease;
  min-width: 4px;
}

/* Texto superpuesto */
.gantt-bar-text {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 3px;
  color: white;
  font-size: 0.68rem;
  font-weight: 700;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.55);
  padding: 0 4px;
  white-space: nowrap;
  overflow: hidden;
}

/* ── Leyenda ──────────────────────────────────────────────────────── */
.gantt-legend {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}

.gantt-filter-chip {
  cursor: pointer;
  transition: opacity 0.18s ease, transform 0.12s ease;
  user-select: none;
}

.gantt-filter-chip:hover {
  transform: translateY(-1px);
}

.gantt-legend-item {
  display: flex;
  align-items: center;
  gap: 5px;
}

.gantt-legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

.gantt-legend-today-line {
  display: inline-block;
  width: 3px;
  height: 14px;
  background: rgb(var(--v-theme-error));
  border-radius: 2px;
  flex-shrink: 0;
}

.gantt-zoom-hint {
  padding: 4px 12px;
  font-size: 0.68rem;
  text-align: right;
  border-bottom: 1px solid rgba(var(--v-border-color), 0.06);
}
</style>
