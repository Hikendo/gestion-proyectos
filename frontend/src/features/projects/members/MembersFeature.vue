<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import FeaturePanel from '../../../components/FeaturePanel.vue';
import RequestState from '../../../components/RequestState.vue';
import ValidationErrors from '../../../components/ValidationErrors.vue';
import { projectMemberRoleOptions } from '../../../constants/domain-options';
import { useProjectMembersService, useUsersService } from '../../../composables';

const props = defineProps({
    projectId: {
        type: Number,
        required: true,
    },
});

const membersQuery = useProjectMembersService();
const membersMutation = useProjectMembersService();
const usersQuery = useUsersService();
const members = ref([]);
const users = ref([]);
const successMessage = ref('');
const form = reactive({
  user_id: '',
  role: 'developer',
});

async function loadMembers() {
  const response = await membersQuery.call('list', props.projectId);

    if (response) {
        members.value = response.data;
    }
}

async function loadUsers() {
  const response = await usersQuery.call('list');

  if (response) {
    users.value = response.data;
  }
}

function resetForm() {
  form.user_id = '';
  form.role = 'developer';
}

async function handleCreate() {
  successMessage.value = '';

  const response = await membersMutation.call('add', props.projectId, {
    user_id: Number(form.user_id),
    role: form.role,
  });

  if (response) {
    successMessage.value = 'Miembro agregado correctamente.';
    resetForm();
    await loadMembers();
  }
}

async function handleRemove(member) {
  successMessage.value = '';

  if (!member.user?.id) {
    return;
  }

  const response = await membersMutation.call('remove', props.projectId, member.user.id);

  if (response) {
    successMessage.value = 'Miembro removido correctamente.';
    await loadMembers();
  }
}

onMounted(loadMembers);
onMounted(loadUsers);

watch(
    () => props.projectId,
    () => {
        loadMembers();
    },
);
</script>

<template>
  <FeaturePanel title="Projects / Members" description="Listado real de miembros del proyecto usando el id de la ruta.">
    <template #actions>
      <button class="button primary" :disabled="membersQuery.loading || usersQuery.loading" type="button" @click="loadMembers">Recargar</button>
    </template>

    <form class="form-grid" @submit.prevent="handleCreate">
      <div class="inline-fields">
        <label>
          <span>Usuario</span>
          <select v-model="form.user_id">
            <option value="">Selecciona un usuario</option>
            <option v-for="user in users" :key="user.id" :value="String(user.id)">{{ user.name }} · {{ user.email }}</option>
          </select>
          <ValidationErrors :errors="membersMutation.validationErrors.user_id || []" />
        </label>

        <label>
          <span>Rol</span>
          <select v-model="form.role">
            <option v-for="role in projectMemberRoleOptions" :key="role.value" :value="role.value">{{ role.label }}</option>
          </select>
          <ValidationErrors :errors="membersMutation.validationErrors.role || []" />
        </label>
      </div>

      <div class="form-actions">
        <button class="button primary" :disabled="membersMutation.loading" type="submit">Agregar miembro</button>
        <button class="button secondary" type="button" @click="resetForm">Limpiar</button>
      </div>
    </form>

    <ul v-if="members.length" class="entity-list">
      <li v-for="member in members" :key="member.id">
        <div>
          <strong>{{ member.user?.name || 'Usuario sin nombre' }}</strong>
          <span>{{ member.role }}</span>
        </div>
        <button class="button danger" :disabled="membersMutation.loading" type="button" @click="handleRemove(member)">Eliminar</button>
      </li>
    </ul>

    <p class="feature-copy">La API actual de members soporta alta y baja. La edicion de rol requerira un endpoint `update` en backend.</p>

    <p v-if="!members.length && !membersQuery.loading" class="feature-copy">No hay miembros registrados para este proyecto.</p>

    <RequestState :loading="membersQuery.loading || membersMutation.loading || usersQuery.loading" :error-message="membersMutation.errorMessage || membersQuery.errorMessage || usersQuery.errorMessage" :success-message="successMessage" />
  </FeaturePanel>
</template>
