<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import FeaturePanel from '../../../components/FeaturePanel.vue';
import RequestState from '../../../components/RequestState.vue';
import ValidationErrors from '../../../components/ValidationErrors.vue';
import { taskPriorityOptions, taskStatusOptions } from '../../../constants/domain-options';
import { useProjectPhasesService, useProjectTasksService, useUsersService } from '../../../composables';

const props = defineProps({
    projectId: {
        type: Number,
        required: true,
    },
});

const tasksQuery = useProjectTasksService();
const tasksMutation = useProjectTasksService();
const usersQuery = useUsersService();
const phasesQuery = useProjectPhasesService();
const tasks = ref([]);
const pagination = ref(null);
const users = ref([]);
const phases = ref([]);
const successMessage = ref('');
const editingId = ref(null);
const form = reactive({
    title: '',
    description: '',
    phase_id: '',
    assigned_to: '',
    priority: '',
    status: 'pending',
    due_date: '',
    estimated_hours: '',
    progress: 0,
});

const isEditing = computed(() => editingId.value !== null);

async function loadTasks() {
    const response = await tasksQuery.call('list', props.projectId);

    if (response) {
        tasks.value = response.data;
        pagination.value = response.meta;
    }
}

async function loadUsers() {
    const response = await usersQuery.call('list');

    if (response) {
        users.value = response.data;
    }
}

async function loadPhases() {
    const response = await phasesQuery.call('list', props.projectId);

    if (response) {
        phases.value = response.data;
    }
}

function resetForm() {
    editingId.value = null;
    form.title = '';
    form.description = '';
    form.phase_id = '';
    form.assigned_to = '';
    form.priority = '';
    form.status = 'pending';
    form.due_date = '';
    form.estimated_hours = '';
    form.progress = 0;
}

function startEdit(task) {
    editingId.value = task.id;
    form.title = task.title || '';
    form.description = task.description || '';
    form.phase_id = task.phase?.id ? String(task.phase.id) : '';
    form.assigned_to = task.assignee?.id ? String(task.assignee.id) : '';
    form.priority = task.priority || '';
    form.status = task.status || 'pending';
    form.due_date = task.due_date || '';
    form.estimated_hours = task.estimated_hours ?? '';
    form.progress = task.progress ?? 0;
}

function buildPayload() {
    return {
        title: form.title,
        description: form.description || null,
        phase_id: form.phase_id ? Number(form.phase_id) : null,
        assigned_to: form.assigned_to ? Number(form.assigned_to) : null,
        priority: form.priority || null,
        status: form.status || null,
        due_date: form.due_date || null,
        estimated_hours: form.estimated_hours === '' ? null : Number(form.estimated_hours),
        ...(isEditing.value ? { progress: Number(form.progress) } : {}),
    };
}

async function handleSubmit() {
    successMessage.value = '';

    const response = isEditing.value
        ? await tasksMutation.call('update', props.projectId, editingId.value, buildPayload())
        : await tasksMutation.call('create', props.projectId, buildPayload());

    if (response) {
        successMessage.value = isEditing.value ? 'Tarea actualizada correctamente.' : 'Tarea creada correctamente.';
        resetForm();
        await loadTasks();
    }
}

async function handleRemove(task) {
    successMessage.value = '';
    const response = await tasksMutation.call('remove', props.projectId, task.id);

    if (response) {
        successMessage.value = 'Tarea eliminada correctamente.';
        if (editingId.value === task.id) {
            resetForm();
        }
        await loadTasks();
    }
}

onMounted(loadTasks);
onMounted(loadUsers);
onMounted(loadPhases);

watch(
    () => props.projectId,
    () => {
        loadTasks();
        loadPhases();
    },
);
</script>

<template>
  <FeaturePanel title="Projects / Tasks" description="Listado real de tareas del proyecto usando el id de la ruta.">
    <template #actions>
      <button class="button primary" :disabled="tasksQuery.loading" type="button" @click="loadTasks">Recargar</button>
    </template>

    <form class="form-grid" @submit.prevent="handleSubmit">
      <label>
        <span>Titulo</span>
        <input v-model="form.title" type="text" placeholder="Implementar vista de backlog">
        <ValidationErrors :errors="tasksMutation.validationErrors.title || []" />
      </label>

      <label>
        <span>Descripcion</span>
        <textarea v-model="form.description" rows="4" />
        <ValidationErrors :errors="tasksMutation.validationErrors.description || []" />
      </label>

      <div class="inline-fields">
        <label>
          <span>Fase</span>
          <select v-model="form.phase_id">
            <option value="">Sin fase</option>
            <option v-for="phase in phases" :key="phase.id" :value="String(phase.id)">{{ phase.name }}</option>
          </select>
          <ValidationErrors :errors="tasksMutation.validationErrors.phase_id || []" />
        </label>

        <label>
          <span>Asignado a</span>
          <select v-model="form.assigned_to">
            <option value="">Sin asignar</option>
            <option v-for="user in users" :key="user.id" :value="String(user.id)">{{ user.name }}</option>
          </select>
          <ValidationErrors :errors="tasksMutation.validationErrors.assigned_to || []" />
        </label>

        <label>
          <span>Prioridad</span>
          <select v-model="form.priority">
            <option v-for="option in taskPriorityOptions" :key="option.label" :value="option.value">{{ option.label }}</option>
          </select>
          <ValidationErrors :errors="tasksMutation.validationErrors.priority || []" />
        </label>

        <label>
          <span>Estado</span>
          <select v-model="form.status">
            <option v-for="option in taskStatusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
          </select>
          <ValidationErrors :errors="tasksMutation.validationErrors.status || []" />
        </label>

        <label>
          <span>Fecha limite</span>
          <input v-model="form.due_date" type="date">
          <ValidationErrors :errors="tasksMutation.validationErrors.due_date || []" />
        </label>

        <label>
          <span>Horas estimadas</span>
          <input v-model="form.estimated_hours" min="0" step="0.5" type="number">
          <ValidationErrors :errors="tasksMutation.validationErrors.estimated_hours || []" />
        </label>

        <label>
          <span>Progreso</span>
          <input v-model.number="form.progress" max="100" min="0" type="number">
          <ValidationErrors :errors="tasksMutation.validationErrors.progress || []" />
        </label>
      </div>

      <div class="form-actions">
        <button class="button primary" :disabled="tasksMutation.loading" type="submit">{{ isEditing ? 'Actualizar tarea' : 'Crear tarea' }}</button>
        <button class="button secondary" type="button" @click="resetForm">Cancelar</button>
      </div>
    </form>

    <ul v-if="tasks.length" class="entity-list">
      <li v-for="task in tasks" :key="task.id">
        <div>
          <strong>{{ task.title }}</strong>
          <span>{{ task.status }} · {{ task.priority || 'sin prioridad' }}</span>
        </div>
        <div class="item-actions">
          <button class="button secondary" type="button" @click="startEdit(task)">Editar</button>
          <button class="button danger" :disabled="tasksMutation.loading" type="button" @click="handleRemove(task)">Eliminar</button>
        </div>
      </li>
    </ul>

    <p v-if="pagination && tasks.length" class="feature-copy">
      Pagina {{ pagination.current_page }} de {{ pagination.last_page }} · {{ pagination.total }} tareas.
    </p>

    <p v-else-if="!tasksQuery.loading" class="feature-copy">No hay tareas registradas para este proyecto.</p>

    <RequestState :loading="tasksQuery.loading || tasksMutation.loading || usersQuery.loading || phasesQuery.loading" :error-message="tasksMutation.errorMessage || tasksQuery.errorMessage || usersQuery.errorMessage || phasesQuery.errorMessage" :success-message="successMessage" />
  </FeaturePanel>
</template>
