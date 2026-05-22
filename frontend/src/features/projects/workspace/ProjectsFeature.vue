<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import FeaturePanel from '../../../components/FeaturePanel.vue';
import RequestState from '../../../components/RequestState.vue';
import { useProjectsService } from '../../../composables';

const { call, loading, errorMessage } = useProjectsService();
const projects = ref([]);

async function loadProjects() {
    const response = await call('list');
    if (response) {
        projects.value = response.data;
    }
}

onMounted(loadProjects);
</script>

<template>
  <FeaturePanel title="Projects" description="Consume el endpoint principal de proyectos paginados.">
    <button class="button primary" :disabled="loading" type="button" @click="loadProjects">Cargar proyectos</button>
    <ul class="entity-list" v-if="projects.length">
      <li v-for="project in projects" :key="project.id">
        <RouterLink :to="{ name: 'projects-detail', params: { id: project.id } }" class="entity-link">
          <strong>{{ project.name }}</strong>
          <span>{{ project.status }}</span>
        </RouterLink>
      </li>
    </ul>
    <RequestState :loading="loading" :error-message="errorMessage" />
  </FeaturePanel>
</template>
