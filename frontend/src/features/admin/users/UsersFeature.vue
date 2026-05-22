<script setup>
import { onMounted, ref } from 'vue';
import FeaturePanel from '../../../components/FeaturePanel.vue';
import RequestState from '../../../components/RequestState.vue';
import ValidationErrors from '../../../components/ValidationErrors.vue';
import { useUsersService } from '../../../composables';

const { call, loading, errorMessage, validationErrors } = useUsersService();
const users = ref([]);

async function loadUsers() {
    const response = await call('list');
    if (response) {
        users.value = response.data;
    }
}

onMounted(loadUsers);
</script>

<template>
  <FeaturePanel title="Admin / Users" description="Consume el listado de usuarios y deja lista la visualización de errores por campo.">
    <button class="button primary" :disabled="loading" type="button" @click="loadUsers">Cargar usuarios</button>
    <ValidationErrors :errors="validationErrors.email || []" />
    <ul class="entity-list" v-if="users.length">
      <li v-for="user in users" :key="user.id">
        <strong>{{ user.name }}</strong>
        <span>{{ user.email }}</span>
      </li>
    </ul>
    <RequestState :loading="loading" :error-message="errorMessage" />
  </FeaturePanel>
</template>
