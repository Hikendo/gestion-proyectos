<script setup>
import { onMounted, ref } from 'vue';
import FeaturePanel from '../../../components/FeaturePanel.vue';
import RequestState from '../../../components/RequestState.vue';
import { useRolesService } from '../../../composables';

const { call, loading, errorMessage } = useRolesService();
const roles = ref([]);

async function loadRoles() {
    const response = await call('list');
    if (response) {
        roles.value = response;
    }
}

onMounted(loadRoles);
</script>

<template>
  <FeaturePanel title="Admin / Roles" description="Lista los roles disponibles y sus permisos desde la API.">
    <ul class="entity-list" v-if="roles.length">
      <li v-for="role in roles" :key="role.name">
        <strong>{{ role.label }}</strong>
        <span>{{ role.name }}</span>
      </li>
    </ul>
    <RequestState :loading="loading" :error-message="errorMessage" />
  </FeaturePanel>
</template>
