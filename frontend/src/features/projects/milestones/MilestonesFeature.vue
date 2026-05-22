<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import FeaturePanel from '../../../components/FeaturePanel.vue';
import RequestState from '../../../components/RequestState.vue';
import ValidationErrors from '../../../components/ValidationErrors.vue';
import { useProjectMilestonesService } from '../../../composables';

const props = defineProps({
    projectId: {
        type: Number,
        required: true,
    },
});

const milestonesQuery = useProjectMilestonesService();
const milestonesMutation = useProjectMilestonesService();
const milestones = ref([]);
const successMessage = ref('');
const editingId = ref(null);
const form = reactive({
    title: '',
    target_date: '',
    completed: false,
});

const isEditing = computed(() => editingId.value !== null);

async function loadMilestones() {
    const response = await milestonesQuery.call('list', props.projectId);

    if (response) {
        milestones.value = response.data;
    }
}

function resetForm() {
    editingId.value = null;
    form.title = '';
    form.target_date = '';
    form.completed = false;
}

function startEdit(milestone) {
    editingId.value = milestone.id;
    form.title = milestone.title || '';
    form.target_date = milestone.target_date || '';
    form.completed = Boolean(milestone.completed);
}

async function handleSubmit() {
    successMessage.value = '';

    const payload = {
        title: form.title,
        target_date: form.target_date || null,
        ...(isEditing.value ? { completed: form.completed } : {}),
    };

    const response = isEditing.value
        ? await milestonesMutation.call('update', props.projectId, editingId.value, payload)
        : await milestonesMutation.call('create', props.projectId, payload);

    if (response) {
        successMessage.value = isEditing.value ? 'Hito actualizado correctamente.' : 'Hito creado correctamente.';
        resetForm();
        await loadMilestones();
    }
}

async function handleRemove(milestone) {
    successMessage.value = '';
    const response = await milestonesMutation.call('remove', props.projectId, milestone.id);

    if (response) {
        successMessage.value = 'Hito eliminado correctamente.';
        if (editingId.value === milestone.id) {
            resetForm();
        }
        await loadMilestones();
    }
}

onMounted(loadMilestones);

watch(
    () => props.projectId,
    () => {
        resetForm();
        loadMilestones();
    },
);
</script>

<template>
  <FeaturePanel title="Projects / Milestones" description="CRUD completo de hitos del proyecto.">
    <template #actions>
      <button class="button primary" :disabled="milestonesQuery.loading" type="button" @click="loadMilestones">Recargar</button>
    </template>

    <form class="form-grid" @submit.prevent="handleSubmit">
      <div class="inline-fields">
        <label>
          <span>Titulo</span>
          <input v-model="form.title" type="text" placeholder="Primer release interno">
          <ValidationErrors :errors="milestonesMutation.validationErrors.title || []" />
        </label>

        <label>
          <span>Fecha objetivo</span>
          <input v-model="form.target_date" type="date">
          <ValidationErrors :errors="milestonesMutation.validationErrors.target_date || []" />
        </label>
      </div>

      <label class="checkbox-field">
        <input v-model="form.completed" type="checkbox">
        <span>Hito completado</span>
      </label>

      <div class="form-actions">
        <button class="button primary" :disabled="milestonesMutation.loading" type="submit">{{ isEditing ? 'Actualizar hito' : 'Crear hito' }}</button>
        <button class="button secondary" type="button" @click="resetForm">Cancelar</button>
      </div>
    </form>

    <ul v-if="milestones.length" class="entity-list">
      <li v-for="milestone in milestones" :key="milestone.id">
        <div>
          <strong>{{ milestone.title }}</strong>
          <span>{{ milestone.target_date || 'sin fecha' }} · {{ milestone.completed ? 'completado' : 'pendiente' }}</span>
        </div>
        <div class="item-actions">
          <button class="button secondary" type="button" @click="startEdit(milestone)">Editar</button>
          <button class="button danger" :disabled="milestonesMutation.loading" type="button" @click="handleRemove(milestone)">Eliminar</button>
        </div>
      </li>
    </ul>

    <p v-if="!milestones.length && !milestonesQuery.loading" class="feature-copy">No hay hitos registrados para este proyecto.</p>

    <RequestState :loading="milestonesQuery.loading || milestonesMutation.loading" :error-message="milestonesMutation.errorMessage || milestonesQuery.errorMessage" :success-message="successMessage" />
  </FeaturePanel>
</template>
