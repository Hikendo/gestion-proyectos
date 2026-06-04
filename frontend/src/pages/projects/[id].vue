<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { useAuthStore } from '@/store/useAuthStore';
import { useEnsureCurrentProject } from '@/composables/useEnsureCurrentProject';
import { canAction } from '@/helpers/canAction';
import * as projectsService from '@/services/projects.service';
import type { ProjectI } from '@/interfaces/ProjectI';
import { formatDate } from '@/utils/util';
import ProjectOverviewTab from '@/pages/project-detail/ProjectOverviewTab.vue';

const route = useRoute();
const router = useRouter();
const appStore = useAppStore();
const authStore = useAuthStore();
const { loader, snackbar } = storeToRefs(appStore);

const project = ref<ProjectI | null>(null);

const projectId = Number(route.params.projectId);

const featureTabs = [
  { key: 'objectives', label: 'Objetivos', icon: 'mdi-flag-checkered', route: 'objectives' },
  { key: 'phases', label: 'Fases', icon: 'mdi-timeline-outline', route: 'phases' },
  { key: 'plans', label: 'Planes', icon: 'mdi-calendar-clock', route: 'plans' },
  { key: 'tasks', label: 'Tareas', icon: 'mdi-check-circle-outline', route: 'tasks' },
  { key: 'tickets', label: 'Tickets', icon: 'mdi-ticket-outline', route: 'tickets' },
  { key: 'risks', label: 'Riesgos', icon: 'mdi-alert-circle-outline', route: 'risks' },
  { key: 'blockers', label: 'Bloqueadores', icon: 'mdi-block-helper', route: 'blockers' },
  { key: 'deliverables', label: 'Entregables', icon: 'mdi-package-variant-closed', route: 'deliverables' },
  { key: 'milestones', label: 'Hitos', icon: 'mdi-map-marker-check', route: 'milestones' },
  { key: 'members', label: 'Miembros', icon: 'mdi-account-group-outline', route: 'members' },
  { key: 'metrics', label: 'Métricas', icon: 'mdi-chart-bar', route: 'metrics' },
  { key: 'reports', label: 'Reportes', icon: 'mdi-file-chart-outline', route: 'project-reports' },
];

const statusColor: Record<string, string> = {
  planning: 'blue-grey', active: 'success',
  on_hold: 'warning', completed: 'primary', cancelled: 'error',
};

async function loadProject() {
  const response = await projectsService.show(projectId);
  if (response.status && response.items) {
    project.value = response.items as ProjectI;
    authStore.setCurrentProject(response.items as ProjectI);
  }
}

onMounted(async () => {
  loader.value = true;
  await loadProject();
  loader.value = false;
});

function navigateToFeature(routeName: string) {
  router.push({ name: routeName, params: { projectId } });
}
</script>

<template>
  <div v-if="project">
    <!-- Header -->
    <VRow class="mb-4">
      <VCol cols="12">
        <VCard>
          <VCardItem>
            <template #prepend>
              <VAvatar :color="statusColor[project.status] ?? 'grey'" variant="tonal" size="48">
                <VIcon icon="mdi-folder-open" />
              </VAvatar>
            </template>
            <VCardTitle class="text-h5">{{ project.name }}</VCardTitle>
            <VCardSubtitle>
              <VChip :color="statusColor[project.status]" variant="tonal" size="small" class="me-2">
                {{ project.status }}
              </VChip>
              <span v-if="project.code" class="text-caption">{{ project.code }}</span>
            </VCardSubtitle>
            <template #append>
              <VBtn variant="outlined" size="small" :to="{ name: 'projects' }" prepend-icon="mdi-arrow-left">
                Proyectos
              </VBtn>
            </template>
          </VCardItem>
        </VCard>
      </VCol>
    </VRow>

    <!-- Tabs de navegación a sub-features -->
    <VRow class="mb-6">
      <VCol cols="12">
        <VCard>
          <VCardItem>
            <VCardTitle class="text-subtitle-1 font-weight-bold mb-3">
              <VIcon icon="mdi-view-grid" class="me-2" />
              Módulos del proyecto
            </VCardTitle>
          </VCardItem>
          <VCardText class="pt-0">
            <VRow>
              <VCol v-for="feat in featureTabs" :key="feat.key" cols="6" sm="4" md="3" lg="2">
                <VCard variant="tonal" color="primary" class="cursor-pointer text-center pa-3"
                  @click="navigateToFeature(feat.route)">
                  <VIcon :icon="feat.icon" size="28" class="mb-1" />
                  <div class="text-caption font-weight-medium">{{ feat.label }}</div>
                </VCard>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Información del proyecto -->
    <VRow>
      <VCol cols="12">
        <VCard>
          <VCardItem>
            <VCardTitle>
              <div class="d-flex justify-space-between flex-wrap align-center">
                <span>
                  <VIcon icon="mdi-information-outline" class="me-2" />
                  Información del proyecto
                </span>
                <VBtn v-if="canAction('Proyecto.Update')" variant="flat" color="warning" prepend-icon="mdi-pencil"
                  :to="{ name: 'project-edit', params: { projectId: projectId } }">
                  Editar proyecto
                </VBtn>
              </div>
            </VCardTitle>
          </VCardItem>
          <VDivider />
          <VCardText>
            <VRow>
              <VCol cols="12" md="6">
                <div class="text-caption text-medium-emphasis">Nombre</div>
                <div class="text-body-1 font-weight-medium">{{ project.name }}</div>
              </VCol>
              <VCol cols="12" md="3">
                <div class="text-caption text-medium-emphasis">Código</div>
                <div class="text-body-1">{{ project.code ?? '—' }}</div>
              </VCol>
              <VCol cols="12" md="3">
                <div class="text-caption text-medium-emphasis">Estado</div>
                <VChip :color="statusColor[project.status] ?? 'grey'" variant="tonal" size="small">
                  {{ project.status }}
                </VChip>
              </VCol>
              <VCol cols="12">
                <div class="text-caption text-medium-emphasis">Descripción</div>
                <div class="text-body-2">{{ project.description ?? '—' }}</div>
              </VCol>
              <VCol cols="12" md="3">
                <div class="text-caption text-medium-emphasis">Fecha inicio</div>
                <div class="text-body-1">{{ formatDate(project.start_date!) ?? '—' }}</div>
              </VCol>
              <VCol cols="12" md="3">
                <div class="text-caption text-medium-emphasis">Fecha fin</div>
                <div class="text-body-1">{{ formatDate(project.end_date!) ?? '—' }}</div>
              </VCol>
              <VCol v-if="canAction('Proyecto.ViewBudget')" cols="12" md="3">
                <div class="text-caption text-medium-emphasis">Presupuesto</div>
                <div class="text-body-1">{{ project.budget ?? '—' }}</div>
              </VCol>
              <VCol cols="12" md="3">
                <div class="text-caption text-medium-emphasis">Progreso</div>
                <div class="text-body-1">{{ project.progress != null ? `${project.progress}%` : '—' }}</div>
              </VCol>
              <VCol cols="12" md="6" v-if="project.owner">
                <div class="text-caption text-medium-emphasis">Responsable</div>
                <div class="text-body-1">{{ project.owner.name }}</div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Expediente digital del proyecto -->
    <VRow class="mt-4">
      <VCol cols="12">
        <ProjectOverviewTab :project-id="projectId" :attachments="project.attachments ?? []"
          :can-delete="canAction('Proyecto.Update')" @refresh="loadProject" />
      </VCol>
    </VRow>
  </div>
</template>
