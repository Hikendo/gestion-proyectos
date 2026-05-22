<script setup>
import { onMounted, ref } from 'vue';
import FeaturePanel from '../../../components/FeaturePanel.vue';
import RequestState from '../../../components/RequestState.vue';
import { useDashboardService } from '../../../composables';

const { call, loading, errorMessage } = useDashboardService();
const summary = ref(null);

async function loadDashboard() {
    const response = await call('get');
    if (response) {
        summary.value = response.summary;
    }
}

onMounted(loadDashboard);
</script>

<template>
  <FeaturePanel title="Dashboard" description="Carga el resumen principal del usuario autenticado.">
    <div v-if="summary" class="stats-grid">
      <article class="stat-card">
        <strong>{{ summary.total_projects }}</strong>
        <span>Proyectos</span>
      </article>
      <article class="stat-card">
        <strong>{{ summary.my_pending_tasks }}</strong>
        <span>Tareas pendientes</span>
      </article>
      <article class="stat-card">
        <strong>{{ summary.open_tickets }}</strong>
        <span>Tickets abiertos</span>
      </article>
    </div>

    <button class="button primary" :disabled="loading" type="button" @click="loadDashboard">Recargar dashboard</button>
    <RequestState :loading="loading" :error-message="errorMessage" />
  </FeaturePanel>
</template>
