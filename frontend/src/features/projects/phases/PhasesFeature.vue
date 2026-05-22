<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import FeaturePanel from '../../../components/FeaturePanel.vue';
import RequestState from '../../../components/RequestState.vue';
import ValidationErrors from '../../../components/ValidationErrors.vue';
import { useProjectPhasesService } from '../../../composables';

const props = defineProps({
    projectId: {
        type: Number,
        required: true,
    },
});

const phasesQuery = useProjectPhasesService();
const phasesMutation = useProjectPhasesService();
const phases = ref([]);
const successMessage = ref('');
const editingId = ref(null);
const form = reactive({
  name: '',
  start_date: '',
  end_date: '',
  progress: 0,
});

const isEditing = computed(() => editingId.value !== null);

async function loadPhases() {
  const response = await phasesQuery.call('list', props.projectId);

    if (response) {
        phases.value = response.data;
    }
}

function resetForm() {
  editingId.value = null;
  form.name = '';
  form.start_date = '';
  form.end_date = '';
  form.progress = 0;
}

function startEdit(phase) {
  editingId.value = phase.id;
  form.name = phase.name || '';
  form.start_date = phase.start_date || '';
  form.end_date = phase.end_date || '';
  form.progress = phase.progress ?? 0;
}

async function handleSubmit() {
  successMessage.value = '';

  const payload = {
    name: form.name,
    start_date: form.start_date || null,
    end_date: form.end_date || null,
    ...(isEditing.value ? { progress: Number(form.progress) } : {}),
  };

  const response = isEditing.value
    ? await phasesMutation.call('update', props.projectId, editingId.value, payload)
    : await phasesMutation.call('create', props.projectId, payload);

  if (response) {
    successMessage.value = isEditing.value ? 'Fase actualizada correctamente.' : 'Fase creada correctamente.';
    resetForm();
    await loadPhases();
  }
}

async function handleRemove(phase) {
  successMessage.value = '';
  const response = await phasesMutation.call('remove', props.projectId, phase.id);

  if (response) {
    successMessage.value = 'Fase eliminada correctamente.';
    if (editingId.value === phase.id) {
      resetForm();
    }
    await loadPhases();
  }
}

onMounted(loadPhases);

watch(
    () => props.projectId,
    () => {
        loadPhases();
    },
);
</script>

<template>
  <FeaturePanel title="Projects / Phases" description="Listado real de fases del proyecto usando el id de la ruta.">
    <template #actions>
      <button class="button primary" :disabled="phasesQuery.loading" type="button" @click="loadPhases">Recargar</button>
    </template>

    <form class="form-grid" @submit.prevent="handleSubmit">
      <div class="inline-fields">
        <label>
          <span>Nombre</span>
          <input v-model="form.name" type="text" placeholder="Descubrimiento">
          <ValidationErrors :errors="phasesMutation.validationErrors.name || []" />
        </label>

        <label>
          <span>Progreso</span>
          <input v-model.number="form.progress" max="100" min="0" type="number">
          <ValidationErrors :errors="phasesMutation.validationErrors.progress || []" />
        </label>

        <label>
          <span>Inicio</span>
          <input v-model="form.start_date" type="date">
          <ValidationErrors :errors="phasesMutation.validationErrors.start_date || []" />
        </label>

        <label>
          <span>Fin</span>
          <input v-model="form.end_date" type="date">
          <ValidationErrors :errors="phasesMutation.validationErrors.end_date || []" />
        </label>
      </div>

      <div class="form-actions">
        <button class="button primary" :disabled="phasesMutation.loading" type="submit">{{ isEditing ? 'Actualizar fase' : 'Crear fase' }}</button>
        <button class="button secondary" type="button" @click="resetForm">Cancelar</button>
      </div>
    </form>

    <ul v-if="phases.length" class="entity-list">
      <li v-for="phase in phases" :key="phase.id">
        <div>
          <strong>{{ phase.name }}</strong>
          <span>{{ phase.progress }}% · {{ phase.tasks_count ?? 0 }} tareas</span>
        </div>
        <div class="item-actions">
          <button class="button secondary" type="button" @click="startEdit(phase)">Editar</button>
          <button class="button danger" :disabled="phasesMutation.loading" type="button" @click="handleRemove(phase)">Eliminar</button>
        </div>
      </li>
    </ul>

    <p v-else-if="!phasesQuery.loading" class="feature-copy">No hay fases registradas para este proyecto.</p>

    <RequestState :loading="phasesQuery.loading || phasesMutation.loading" :error-message="phasesMutation.errorMessage || phasesQuery.errorMessage" :success-message="successMessage" />
  </FeaturePanel>
</template>
