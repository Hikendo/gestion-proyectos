<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { canAction } from '@/helpers/canAction';
import { useEnsureCurrentProject } from '@/composables/useEnsureCurrentProject';
import * as phasesService from '@/services/project-phases.service';
import type { PaginacionYQueryI } from '@/interfaces/PaginacionScoutI';
import { formatDate } from '@/utils/util';
import type { ProjectPhaseI } from '@/interfaces/ProjectPhaseI';

const route = useRoute();
const router = useRouter();
const appStore = useAppStore();
const { loader, snackbar } = storeToRefs(appStore);
const projectId = () => Number(route.params.projectId);

useEnsureCurrentProject();

const isDialogVisible = ref(false);
const paginacionYquery = ref<PaginacionYQueryI>({ page: 1, query: '', last_page: 1 });
const data = ref<ProjectPhaseI[]>([]);

// Función para determinar el color de la fase según progreso y fechas
const getPhaseColor = (phase: ProjectPhaseI) => {
  const progress = phase.progress || 0;
  const endDate = phase.end_date ? new Date(phase.end_date) : null;
  const today = new Date();
  const daysRemaining = endDate ? Math.ceil((endDate.getTime() - today.getTime()) / (1000 * 3600 * 24)) : null;

  // Si la fecha ya pasó y el progreso no es 100%
  if (endDate && endDate < today && progress < 100) {
    return 'error';
  }

  // Si el progreso es 100%
  if (progress === 100) {
    return 'success';
  }

  // Si faltan menos de 3 días y el progreso no está completo
  if (daysRemaining !== null && daysRemaining <= 3 && daysRemaining >= 0 && progress < 100) {
    return 'warning';
  }

  // Si faltan entre 4 y 7 días
  if (daysRemaining !== null && daysRemaining <= 7 && daysRemaining > 3 && progress < 80) {
    return 'info';
  }

  return 'secondary';
};

// Función para obtener el color del texto según el color de la fase
const getTextColor = (color: string) => {
  const colorMap: Record<string, string> = {
    error: 'white',
    warning: 'black',
    success: 'white',
    info: 'white',
    primary: 'white'
  };
  return colorMap[color] || 'white';
};

// Función para obtener el color del progreso
const getProgressColor = (phase: ProjectPhaseI) => {
  const progress = phase.progress || 0;
  const endDate = phase.end_date ? new Date(phase.end_date) : null;
  const today = new Date();

  if (endDate && endDate < today && progress < 100) return 'error';
  if (progress === 100) return 'success';
  if (progress >= 70) return 'success';
  if (progress >= 40) return 'warning';
  return 'info';
};
// Función para obtener el icono según el estado
const getStatusIcon = (phase: ProjectPhaseI) => {
  const progress = phase.progress || 0;
  const endDate = phase.end_date ? new Date(phase.end_date) : null;
  const today = new Date();

  if (progress === 100) return 'ri-checkbox-circle-fill';
  if (endDate && endDate < today) return 'ri-error-warning-fill';
  if (endDate) {
    const daysRemaining = Math.ceil((endDate.getTime() - today.getTime()) / (1000 * 3600 * 24));
    if (daysRemaining <= 3) return 'ri-alert-fill';
    if (daysRemaining <= 7) return 'ri-alarm-warning-line';
  }
  return 'ri-progress-5-line';
};

// Función para obtener el texto del estado
const getStatusText = (phase: ProjectPhaseI) => {
  const progress = phase.progress || 0;
  const endDate = phase.end_date ? new Date(phase.end_date) : null;
  const today = new Date();

  if (progress === 100) return 'Completada';
  if (endDate && endDate < today) return 'Atrasada';
  if (endDate) {
    const daysRemaining = Math.ceil((endDate.getTime() - today.getTime()) / (1000 * 3600 * 24));
    if (daysRemaining <= 3) return 'Urgente';
    if (daysRemaining <= 7) return 'Próximo a vencer';
  }
  return 'En progreso';
};

const handleGetData = async () => {
  loader.value = true;
  const response = await phasesService.index(projectId());
  if (response.status && response.items) {
    data.value = (response.items as any).data ?? (Array.isArray(response.items) ? response.items : []);
    paginacionYquery.value.last_page = (response.items as any).last_page ?? 1;
  }
  loader.value = false;
};

const itemDestroy = ref<ProjectPhaseI | null>(null);
const handleDestroy = async () => {
  if (!itemDestroy.value) return;
  loader.value = true;
  const response = await phasesService.destroy(projectId(), itemDestroy.value.id);
  if (response.status) {
    snackbar.value = { show: true, text: 'Fase eliminada', color: 'success' };
    handleGetData();
  }
  loader.value = false;
  isDialogVisible.value = false;
};

// Navegar a la vista de tareas de la fase
const goToPhaseTasks = (phaseId: number) => {
  router.push({
    name: 'phases-id',
    params: { projectId: projectId(), id: phaseId }
  });
};

watch(() => isDialogVisible.value, v => { if (!v) itemDestroy.value = null; });
onMounted(handleGetData);
</script>
<template>
  <!-- Cabecera -->
  <div class="d-flex justify-space-between align-center mb-4">
    <h2 class="text-h5 font-weight-bold">Fases del proyecto</h2>
    <VBtn color="primary" prepend-icon="ri-add-line"
      @click="router.push({ name: 'phases-new', params: { projectId: projectId() } })" v-if="canAction('phase.create')">
      Nueva Fase
    </VBtn>
  </div>

  <!-- Tabla de fases -->
  <VTable height="500" fixed-header>
    <thead>
      <tr>
        <th class="text-uppercase">Nombre</th>
        <th class="text-uppercase">Progreso</th>
        <th class="text-uppercase">Estado</th>
        <th class="text-uppercase">Inicio</th>
        <th class="text-uppercase">Fin</th>
        <th class="text-uppercase">Tareas</th>
        <th class="text-uppercase">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="item in data" :key="item.id">
        <td>
          <div class="d-flex align-center gap-2">
            <VIcon :icon="getStatusIcon(item)" :color="getPhaseColor(item)" size="small" />
            <strong class="ml-2">{{ item.name }}</strong>
          </div>
        </td>
        <td>
          <div class="d-flex align-center gap-2">
            <VProgressLinear :model-value="item.progress || 0" :color="getProgressColor(item)" height="8" rounded
              class="flex-grow-1" />
            <span class="text-body-2 font-weight-medium ml-1">{{ item.progress || 0 }}%</span>
          </div>
        </td>
        <td>
          <VChip :color="getPhaseColor(item)" size="small" variant="flat">{{ getStatusText(item) }}</VChip>
        </td>
        <td>{{ formatDate(item.start_date!) ?? '—' }}</td>
        <td>{{ formatDate(item.end_date!) ?? '—' }}</td>
        <td>
          <VChip size="small" color="primary" variant="tonal" @click="goToPhaseTasks(item.id)" class="cursor-pointer">
            {{ item.tasks_count ?? 0 }} tareas
          </VChip>
        </td>
        <td>
          <div class="d-flex gap-1">
            <VBtn icon size="small" variant="text"
              @click="router.push({ name: 'phases-view', params: { projectId: projectId(), id: item.id } })">
              <VIcon icon="ri-eye-line" color="primary" size="small" />
            </VBtn>
            <VBtn icon size="small" variant="text"
              @click="router.push({ name: 'phases-id', params: { projectId: projectId(), id: item.id } })">
              <VIcon icon="ri-pencil-line" color="warning" size="small" />
            </VBtn>
            <VBtn icon size="small" variant="text" @click="itemDestroy = item; isDialogVisible = true">
              <VIcon icon="ri-delete-bin-fill" color="error" size="small" />
            </VBtn>
          </div>
        </td>
      </tr>
    </tbody>
  </VTable>

  <!-- Diálogo de confirmación de eliminación -->
  <VDialog v-model="isDialogVisible" persistent max-width="400">
    <VCard>
      <VCardTitle class="text-h6">Confirmar eliminación</VCardTitle>
      <VCardText>
        ¿Estás seguro de que deseas eliminar la fase <strong>{{ itemDestroy?.name }}</strong>?
        Esta acción no se puede deshacer.
      </VCardText>
      <VCardActions class="justify-end gap-2 pb-4 px-4">
        <VBtn variant="outlined" @click="isDialogVisible = false; itemDestroy = null">Cancelar</VBtn>
        <VBtn color="error" variant="flat" :loading="loader" @click="handleDestroy">Eliminar</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
