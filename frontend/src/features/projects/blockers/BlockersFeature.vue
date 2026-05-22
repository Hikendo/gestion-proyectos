<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import FeaturePanel from '../../../components/FeaturePanel.vue';
import RequestState from '../../../components/RequestState.vue';
import ValidationErrors from '../../../components/ValidationErrors.vue';
import { blockerSeverityOptions } from '../../../constants/domain-options';
import { useProjectBlockersService, useProjectTasksService } from '../../../composables';

const props = defineProps({
    projectId: {
        type: Number,
        required: true,
    },
});

const blockersQuery = useProjectBlockersService();
const blockersMutation = useProjectBlockersService();
const tasksQuery = useProjectTasksService();
const blockers = ref([]);
const tasks = ref([]);
const successMessage = ref('');
const editingId = ref(null);
const form = reactive({
    title: '',
    description: '',
    severity: 'medium',
    task_id: '',
});

const isEditing = computed(() => editingId.value !== null);

async function loadBlockers() {
    const response = await blockersQuery.call('list', props.projectId, { include_resolved: true });

    if (response) {
        blockers.value = response.data;
    }
}

async function loadTasks() {
    const response = await tasksQuery.call('list', props.projectId);

    if (response) {
        tasks.value = response.data;
    }
}

function resetForm() {
    editingId.value = null;
    form.title = '';
    form.description = '';
    form.severity = 'medium';
    form.task_id = '';
}

function startEdit(blocker) {
    editingId.value = blocker.id;
    form.title = blocker.title || '';
    form.description = blocker.description || '';
    form.severity = blocker.severity || 'medium';
    form.task_id = blocker.task?.id ? String(blocker.task.id) : '';
}

async function handleSubmit() {
    successMessage.value = '';

    const payload = {
        title: form.title,
        description: form.description || null,
        severity: form.severity,
        ...(isEditing.value ? {} : { task_id: form.task_id ? Number(form.task_id) : null }),
    };

    const response = isEditing.value
        ? await blockersMutation.call('update', props.projectId, editingId.value, payload)
        : await blockersMutation.call('create', props.projectId, payload);

    if (response) {
        successMessage.value = isEditing.value ? 'Bloqueador actualizado correctamente.' : 'Bloqueador creado correctamente.';
        resetForm();
        await loadBlockers();
    }
}

async function handleResolve(blocker) {
    successMessage.value = '';
    const response = await blockersMutation.call('resolve', props.projectId, blocker.id);

    if (response) {
        successMessage.value = 'Bloqueador resuelto correctamente.';
        await loadBlockers();
    }
}

onMounted(loadBlockers);
onMounted(loadTasks);

watch(
    () => props.projectId,
    () => {
        resetForm();
        loadBlockers();
        loadTasks();
    },
);
</script>

<template>
  <FeaturePanel title="Projects / Blockers" description="Alta, edicion y resolucion de bloqueadores del proyecto.">
    <template #actions>
      <button class="button primary" :disabled="blockersQuery.loading" type="button" @click="loadBlockers">Recargar</button>
    </template>

    <form class="form-grid" @submit.prevent="handleSubmit">
      <label>
        <span>Titulo</span>
        <input v-model="form.title" type="text" placeholder="Ambiente de QA caido">
        <ValidationErrors :errors="blockersMutation.validationErrors.title || []" />
      </label>

      <label>
        <span>Descripcion</span>
        <textarea v-model="form.description" rows="4" />
        <ValidationErrors :errors="blockersMutation.validationErrors.description || []" />
      </label>

      <div class="inline-fields">
        <label>
          <span>Severidad</span>
          <select v-model="form.severity">
            <option v-for="option in blockerSeverityOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
          </select>
          <ValidationErrors :errors="blockersMutation.validationErrors.severity || []" />
        </label>

        <label>
          <span>Tarea relacionada</span>
          <select v-model="form.task_id" :disabled="isEditing">
            <option value="">Sin tarea</option>
            <option v-for="task in tasks" :key="task.id" :value="String(task.id)">{{ task.title }}</option>
          </select>
          <ValidationErrors :errors="blockersMutation.validationErrors.task_id || []" />
        </label>
      </div>

      <div class="form-actions">
        <button class="button primary" :disabled="blockersMutation.loading" type="submit">{{ isEditing ? 'Actualizar bloqueador' : 'Crear bloqueador' }}</button>
        <button class="button secondary" type="button" @click="resetForm">Cancelar</button>
      </div>
    </form>

    <ul v-if="blockers.length" class="entity-list">
      <li v-for="blocker in blockers" :key="blocker.id">
        <div>
          <strong>{{ blocker.title }}</strong>
          <span>{{ blocker.severity }} · {{ blocker.resolved ? 'resuelto' : 'abierto' }}</span>
        </div>
        <div class="item-actions">
          <button class="button secondary" type="button" @click="startEdit(blocker)">Editar</button>
          <button v-if="!blocker.resolved" class="button primary" :disabled="blockersMutation.loading" type="button" @click="handleResolve(blocker)">Resolver</button>
        </div>
      </li>
    </ul>

    <p class="feature-copy">La API de blockers no expone eliminacion; la accion operativa disponible es resolver.</p>

    <p v-if="!blockers.length && !blockersQuery.loading" class="feature-copy">No hay bloqueadores registrados para este proyecto.</p>

    <RequestState :loading="blockersQuery.loading || blockersMutation.loading || tasksQuery.loading" :error-message="blockersMutation.errorMessage || blockersQuery.errorMessage || tasksQuery.errorMessage" :success-message="successMessage" />
  </FeaturePanel>
</template>
