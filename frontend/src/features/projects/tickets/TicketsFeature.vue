<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import FeaturePanel from '../../../components/FeaturePanel.vue';
import RequestState from '../../../components/RequestState.vue';
import ValidationErrors from '../../../components/ValidationErrors.vue';
import { ticketPriorityOptions, ticketStatusOptions } from '../../../constants/domain-options';
import { useTicketsService, useUsersService } from '../../../composables';

const props = defineProps({
    projectId: {
        type: Number,
        required: true,
    },
});

const ticketsQuery = useTicketsService();
const ticketsMutation = useTicketsService();
const usersQuery = useUsersService();
const tickets = ref([]);
const pagination = ref(null);
const users = ref([]);
const successMessage = ref('');
const editingId = ref(null);
const form = reactive({
  subject: '',
  description: '',
  priority: '',
  status: 'open',
  assigned_to: '',
});

const isEditing = computed(() => editingId.value !== null);

async function loadTickets() {
  const response = await ticketsQuery.call('list', props.projectId);

    if (response) {
        tickets.value = response.data;
        pagination.value = response.meta;
    }
}

async function loadUsers() {
  const response = await usersQuery.call('list');

  if (response) {
    users.value = response.data;
  }
}

function resetForm() {
  editingId.value = null;
  form.subject = '';
  form.description = '';
  form.priority = '';
  form.status = 'open';
  form.assigned_to = '';
}

function startEdit(ticket) {
  editingId.value = ticket.id;
  form.subject = ticket.subject || '';
  form.description = ticket.description || '';
  form.priority = ticket.priority || '';
  form.status = ticket.status || 'open';
  form.assigned_to = ticket.assignee?.id ? String(ticket.assignee.id) : '';
}

function buildPayload() {
  return {
    subject: form.subject,
    description: form.description || null,
    priority: form.priority || null,
    status: form.status || null,
    assigned_to: form.assigned_to ? Number(form.assigned_to) : null,
  };
}

async function handleSubmit() {
  successMessage.value = '';

  const response = isEditing.value
    ? await ticketsMutation.call('update', props.projectId, editingId.value, buildPayload())
    : await ticketsMutation.call('create', props.projectId, buildPayload());

  if (response) {
    successMessage.value = isEditing.value ? 'Ticket actualizado correctamente.' : 'Ticket creado correctamente.';
    resetForm();
    await loadTickets();
  }
}

async function handleRemove(ticket) {
  successMessage.value = '';
  const response = await ticketsMutation.call('remove', props.projectId, ticket.id);

  if (response) {
    successMessage.value = 'Ticket eliminado correctamente.';
    if (editingId.value === ticket.id) {
      resetForm();
    }
    await loadTickets();
  }
}

onMounted(loadTickets);
onMounted(loadUsers);

watch(
    () => props.projectId,
    () => {
        loadTickets();
    },
);
</script>

<template>
  <FeaturePanel title="Projects / Tickets" description="Listado real de tickets del proyecto usando el id de la ruta.">
    <template #actions>
      <button class="button primary" :disabled="ticketsQuery.loading" type="button" @click="loadTickets">Recargar</button>
    </template>

    <form class="form-grid" @submit.prevent="handleSubmit">
      <label>
        <span>Asunto</span>
        <input v-model="form.subject" type="text" placeholder="Error al exportar reporte">
        <ValidationErrors :errors="ticketsMutation.validationErrors.subject || []" />
      </label>

      <label>
        <span>Descripcion</span>
        <textarea v-model="form.description" rows="4" />
        <ValidationErrors :errors="ticketsMutation.validationErrors.description || []" />
      </label>

      <div class="inline-fields">
        <label>
          <span>Prioridad</span>
          <select v-model="form.priority">
            <option v-for="option in ticketPriorityOptions" :key="option.label" :value="option.value">{{ option.label }}</option>
          </select>
          <ValidationErrors :errors="ticketsMutation.validationErrors.priority || []" />
        </label>

        <label>
          <span>Estado</span>
          <select v-model="form.status">
            <option v-for="option in ticketStatusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
          </select>
          <ValidationErrors :errors="ticketsMutation.validationErrors.status || []" />
        </label>

        <label>
          <span>Asignado a</span>
          <select v-model="form.assigned_to">
            <option value="">Sin asignar</option>
            <option v-for="user in users" :key="user.id" :value="String(user.id)">{{ user.name }}</option>
          </select>
          <ValidationErrors :errors="ticketsMutation.validationErrors.assigned_to || []" />
        </label>
      </div>

      <div class="form-actions">
        <button class="button primary" :disabled="ticketsMutation.loading" type="submit">{{ isEditing ? 'Actualizar ticket' : 'Crear ticket' }}</button>
        <button class="button secondary" type="button" @click="resetForm">Cancelar</button>
      </div>
    </form>

    <ul v-if="tickets.length" class="entity-list">
      <li v-for="ticket in tickets" :key="ticket.id">
        <div>
          <strong>{{ ticket.subject }}</strong>
          <span>{{ ticket.status }} · {{ ticket.priority || 'sin prioridad' }}</span>
        </div>
        <div class="item-actions">
          <button class="button secondary" type="button" @click="startEdit(ticket)">Editar</button>
          <button class="button danger" :disabled="ticketsMutation.loading" type="button" @click="handleRemove(ticket)">Eliminar</button>
        </div>
      </li>
    </ul>

    <p v-if="pagination && tickets.length" class="feature-copy">
      Pagina {{ pagination.current_page }} de {{ pagination.last_page }} · {{ pagination.total }} tickets.
    </p>

    <p v-else-if="!ticketsQuery.loading" class="feature-copy">No hay tickets registrados para este proyecto.</p>

    <RequestState :loading="ticketsQuery.loading || ticketsMutation.loading || usersQuery.loading" :error-message="ticketsMutation.errorMessage || ticketsQuery.errorMessage || usersQuery.errorMessage" :success-message="successMessage" />
  </FeaturePanel>
</template>
