<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import FeaturePanel from '../../../components/FeaturePanel.vue';
import RequestState from '../../../components/RequestState.vue';
import ValidationErrors from '../../../components/ValidationErrors.vue';
import { riskImpactOptions, riskProbabilityOptions } from '../../../constants/domain-options';
import { useProjectRisksService } from '../../../composables';

const props = defineProps({
    projectId: {
        type: Number,
        required: true,
    },
});

const risksQuery = useProjectRisksService();
const risksMutation = useProjectRisksService();
const risks = ref([]);
const successMessage = ref('');
const editingId = ref(null);
const form = reactive({
    title: '',
    description: '',
    impact: 'medium',
    probability: 'medium',
    mitigation_plan: '',
});

const isEditing = computed(() => editingId.value !== null);

async function loadRisks() {
    const response = await risksQuery.call('list', props.projectId);

    if (response) {
        risks.value = response.data;
    }
}

function resetForm() {
    editingId.value = null;
    form.title = '';
    form.description = '';
    form.impact = 'medium';
    form.probability = 'medium';
    form.mitigation_plan = '';
}

function startEdit(risk) {
    editingId.value = risk.id;
    form.title = risk.title || '';
    form.description = risk.description || '';
    form.impact = risk.impact || 'medium';
    form.probability = risk.probability || 'medium';
    form.mitigation_plan = risk.mitigation_plan || '';
}

async function handleSubmit() {
    successMessage.value = '';

    const payload = {
        title: form.title,
        description: form.description || null,
        impact: form.impact,
        probability: form.probability,
        mitigation_plan: form.mitigation_plan || null,
    };

    const response = isEditing.value
        ? await risksMutation.call('update', props.projectId, editingId.value, payload)
        : await risksMutation.call('create', props.projectId, payload);

    if (response) {
        successMessage.value = isEditing.value ? 'Riesgo actualizado correctamente.' : 'Riesgo creado correctamente.';
        resetForm();
        await loadRisks();
    }
}

async function handleRemove(risk) {
    successMessage.value = '';
    const response = await risksMutation.call('remove', props.projectId, risk.id);

    if (response) {
        successMessage.value = 'Riesgo eliminado correctamente.';
        if (editingId.value === risk.id) {
            resetForm();
        }
        await loadRisks();
    }
}

onMounted(loadRisks);

watch(
    () => props.projectId,
    () => {
        resetForm();
        loadRisks();
    },
);
</script>

<template>
  <FeaturePanel title="Projects / Risks" description="CRUD completo de riesgos del proyecto.">
    <template #actions>
      <button class="button primary" :disabled="risksQuery.loading" type="button" @click="loadRisks">Recargar</button>
    </template>

    <form class="form-grid" @submit.prevent="handleSubmit">
      <label>
        <span>Titulo</span>
        <input v-model="form.title" type="text" placeholder="Dependencia externa critica">
        <ValidationErrors :errors="risksMutation.validationErrors.title || []" />
      </label>

      <label>
        <span>Descripcion</span>
        <textarea v-model="form.description" rows="4" />
        <ValidationErrors :errors="risksMutation.validationErrors.description || []" />
      </label>

      <div class="inline-fields">
        <label>
          <span>Impacto</span>
          <select v-model="form.impact">
            <option v-for="option in riskImpactOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
          </select>
          <ValidationErrors :errors="risksMutation.validationErrors.impact || []" />
        </label>

        <label>
          <span>Probabilidad</span>
          <select v-model="form.probability">
            <option v-for="option in riskProbabilityOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
          </select>
          <ValidationErrors :errors="risksMutation.validationErrors.probability || []" />
        </label>
      </div>

      <label>
        <span>Plan de mitigacion</span>
        <textarea v-model="form.mitigation_plan" rows="4" />
        <ValidationErrors :errors="risksMutation.validationErrors.mitigation_plan || []" />
      </label>

      <div class="form-actions">
        <button class="button primary" :disabled="risksMutation.loading" type="submit">{{ isEditing ? 'Actualizar riesgo' : 'Crear riesgo' }}</button>
        <button class="button secondary" type="button" @click="resetForm">Cancelar</button>
      </div>
    </form>

    <ul v-if="risks.length" class="entity-list">
      <li v-for="risk in risks" :key="risk.id">
        <div>
          <strong>{{ risk.title }}</strong>
          <span>{{ risk.impact }} · {{ risk.probability }}</span>
        </div>
        <div class="item-actions">
          <button class="button secondary" type="button" @click="startEdit(risk)">Editar</button>
          <button class="button danger" :disabled="risksMutation.loading" type="button" @click="handleRemove(risk)">Eliminar</button>
        </div>
      </li>
    </ul>

    <p v-if="!risks.length && !risksQuery.loading" class="feature-copy">No hay riesgos registrados para este proyecto.</p>

    <RequestState :loading="risksQuery.loading || risksMutation.loading" :error-message="risksMutation.errorMessage || risksQuery.errorMessage" :success-message="successMessage" />
  </FeaturePanel>
</template>
