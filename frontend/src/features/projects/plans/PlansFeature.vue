<script setup>
import { onMounted, ref, watch } from 'vue';
import FeaturePanel from '../../../components/FeaturePanel.vue';
import RequestState from '../../../components/RequestState.vue';
import { useProjectPlansService } from '../../../composables';

const props = defineProps({
    projectId: {
        type: Number,
        required: true,
    },
});

const { call, loading, errorMessage } = useProjectPlansService();
const plan = ref(null);

async function loadPlan() {
    const response = await call('get', props.projectId);

    if (response !== null) {
        plan.value = response;
    }
}

onMounted(loadPlan);

watch(
    () => props.projectId,
    () => {
        loadPlan();
    },
);
</script>

<template>
  <FeaturePanel title="Projects / Plan" description="Plan actual del proyecto cargado desde la API.">
    <template #actions>
      <button class="button primary" :disabled="loading" type="button" @click="loadPlan">Recargar</button>
    </template>

    <div v-if="plan" class="detail-grid">
      <article class="detail-card">
        <h3>Alcance</h3>
        <p>{{ plan.scope || 'Sin definir' }}</p>
      </article>
      <article class="detail-card">
        <h3>Requerimientos</h3>
        <p>{{ plan.requirements || 'Sin definir' }}</p>
      </article>
      <article class="detail-card">
        <h3>Notas tecnicas</h3>
        <p>{{ plan.technical_notes || 'Sin definir' }}</p>
      </article>
    </div>

    <p v-else-if="!loading" class="feature-copy">Este proyecto no tiene un plan registrado.</p>

    <RequestState :loading="loading" :error-message="errorMessage" />
  </FeaturePanel>
</template>
