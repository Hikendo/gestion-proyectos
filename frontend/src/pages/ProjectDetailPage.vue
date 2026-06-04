<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, RouterView } from 'vue-router';
import FeaturePanel from '../components/FeaturePanel.vue';
import RequestState from '../components/RequestState.vue';
import { useProjectsService } from '../composables';

const props = defineProps({
  projectId: {
    type: Number,
    required: true,
  },
});

const tabs = computed(() => [
  { name: 'project-overview', label: 'Overview' },
  { name: 'project-planning', label: 'Planning' },
  { name: 'project-execution', label: 'Execution' },
  { name: 'project-delivery', label: 'Delivery & Risk' },
  { name: 'project-reports', label: 'Reportes' },
]);

const { call, loading, errorMessage } = useProjectsService();
const project = ref(null);

const projectLabel = computed(() => project.value?.name || `Proyecto #${props.projectId}`);
const projectMeta = computed(() => {
  if (!project.value) {
    return [];
  }

  return [
    { label: 'Codigo', value: project.value.code || 'Sin codigo' },
    { label: 'Estado', value: project.value.status || 'Sin estado' },
    { label: 'Progreso', value: `${project.value.progress ?? 0}%` },
    { label: 'Propietario', value: project.value.owner?.name || 'Sin asignar' },
    { label: 'Tareas', value: String(project.value.tasks_count ?? 0) },
    { label: 'Tickets', value: String(project.value.tickets_count ?? 0) },
  ];
});

async function loadProjectDetail() {
  const response = await call('get', props.projectId);

  if (response) {
    project.value = response.data;
  }
}

onMounted(loadProjectDetail);

watch(
  () => props.projectId,
  () => {
    loadProjectDetail();
  },
);
</script>

<template>
  <section class="page-grid">
    <FeaturePanel :title="projectLabel"
      description="El ID del proyecto llega desde la ruta y las secciones del detalle se separan en subrutas hijas.">
      <template #actions>
        <RouterLink :to="{ name: 'projects' }" class="button primary">Volver al listado</RouterLink>
      </template>

      <p class="feature-copy">
        Ruta actual tipada con el parametro <strong>{{ projectId }}</strong>. Las areas de planning, execution y
        delivery ya
        no viven en una sola vista larga.
      </p>

      <div v-if="projectMeta.length" class="stats-grid">
        <article v-for="item in projectMeta" :key="item.label" class="stat-card">
          <strong>{{ item.value }}</strong>
          <span>{{ item.label }}</span>
        </article>
      </div>

      <nav class="project-tabs">
        <RouterLink v-for="tab in tabs" :key="tab.name" :to="{ name: tab.name, params: { projectId } }"
          class="project-tabs__link">
          {{ tab.label }}
        </RouterLink>
      </nav>

      <RequestState :loading="loading" :error-message="errorMessage" />
    </FeaturePanel>

    <RouterView />
  </section>
</template>
