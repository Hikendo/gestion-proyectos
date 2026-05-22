<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import FeaturePanel from '../../../components/FeaturePanel.vue';
import RequestState from '../../../components/RequestState.vue';
import ValidationErrors from '../../../components/ValidationErrors.vue';
import { objectiveTypeOptions } from '../../../constants/domain-options';
import { useProjectObjectivesService } from '../../../composables';

const props = defineProps({
    projectId: {
        type: Number,
        required: true,
    },
});

const objectivesQuery = useProjectObjectivesService();
const objectivesMutation = useProjectObjectivesService();
const objectives = ref([]);
const successMessage = ref('');
const editingId = ref(null);
const form = reactive({
    type: 'general',
    title: '',
    description: '',
    completed: false,
});

const isEditing = computed(() => editingId.value !== null);

async function loadObjectives() {
    const response = await objectivesQuery.call('list', props.projectId);

    if (response) {
        objectives.value = response.data;
    }
}

function resetForm() {
    editingId.value = null;
    form.type = 'general';
    form.title = '';
    form.description = '';
    form.completed = false;
}

function startEdit(objective) {
    editingId.value = objective.id;
    form.type = objective.type || 'general';
    form.title = objective.title || '';
    form.description = objective.description || '';
    form.completed = Boolean(objective.completed);
}

async function handleSubmit() {
    successMessage.value = '';

    const payload = isEditing.value
        ? {
              title: form.title,
              description: form.description || null,
              completed: form.completed,
          }
        : {
              type: form.type,
              title: form.title,
              description: form.description || null,
          };

    const response = isEditing.value
        ? await objectivesMutation.call('update', props.projectId, editingId.value, payload)
        : await objectivesMutation.call('create', props.projectId, payload);

    if (response) {
        successMessage.value = isEditing.value ? 'Objetivo actualizado correctamente.' : 'Objetivo creado correctamente.';
        resetForm();
        await loadObjectives();
    }
}

onMounted(loadObjectives);

watch(
    () => props.projectId,
    () => {
        resetForm();
        loadObjectives();
    },
);
</script>

<template>
  <FeaturePanel title="Projects / Objectives" description="CRUD parcial de objetivos del proyecto.">
    <template #actions>
      <button class="button primary" :disabled="objectivesQuery.loading" type="button" @click="loadObjectives">Recargar</button>
    </template>

    <form class="form-grid" @submit.prevent="handleSubmit">
      <div class="inline-fields">
        <label>
          <span>Tipo</span>
          <select v-model="form.type" :disabled="isEditing">
            <option v-for="option in objectiveTypeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
          </select>
          <ValidationErrors :errors="objectivesMutation.validationErrors.type || []" />
        </label>

        <label>
          <span>Titulo</span>
          <input v-model="form.title" type="text" placeholder="Reducir tiempos de entrega">
          <ValidationErrors :errors="objectivesMutation.validationErrors.title || []" />
        </label>
      </div>

      <label>
        <span>Descripcion</span>
        <textarea v-model="form.description" rows="4" />
        <ValidationErrors :errors="objectivesMutation.validationErrors.description || []" />
      </label>

      <label class="checkbox-field">
        <input v-model="form.completed" type="checkbox">
        <span>Objetivo completado</span>
      </label>

      <div class="form-actions">
        <button class="button primary" :disabled="objectivesMutation.loading" type="submit">{{ isEditing ? 'Actualizar objetivo' : 'Crear objetivo' }}</button>
        <button class="button secondary" type="button" @click="resetForm">Cancelar</button>
      </div>
    </form>

    <ul v-if="objectives.length" class="entity-list">
      <li v-for="objective in objectives" :key="objective.id">
        <div>
          <strong>{{ objective.title }}</strong>
          <span>{{ objective.type }} · {{ objective.completed ? 'completado' : 'pendiente' }}</span>
        </div>
        <div class="item-actions">
          <button class="button secondary" type="button" @click="startEdit(objective)">Editar</button>
        </div>
      </li>
    </ul>

    <p class="feature-copy">La API de objectives permite crear y actualizar, pero no expone un endpoint de eliminacion.</p>

    <p v-if="!objectives.length && !objectivesQuery.loading" class="feature-copy">No hay objetivos registrados para este proyecto.</p>

    <RequestState :loading="objectivesQuery.loading || objectivesMutation.loading" :error-message="objectivesMutation.errorMessage || objectivesQuery.errorMessage" :success-message="successMessage" />
  </FeaturePanel>
</template>
