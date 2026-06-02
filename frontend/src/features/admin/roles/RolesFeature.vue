<script setup>
import { onMounted, ref } from 'vue';
import FeaturePanel from '../../../components/FeaturePanel.vue';
import RequestState from '../../../components/RequestState.vue';
import { useRoles } from '../../../composables/useRolesList';

const { roles, loading, errorMessage, loadRoles } = useRoles();
const dialog = ref(false);
const selectedRole = ref(null);

onMounted(loadRoles);

function showAllPermissions(role) {
  selectedRole.value = role;
  dialog.value = true;
}
</script>

<template>
  <FeaturePanel
    title="Administración / Roles"
    description="Lista de roles disponibles con sus etiquetas y permisos asignados."
  >
    <!-- Tabla Vuetify -->
    <v-table
      v-if="!loading && roles.length"
      density="comfortable"
      fixed-header
      class="rounded-lg"
    >
      <thead>
        <tr>
          <th class="text-left">Nombre</th>
          <th class="text-left">Etiqueta</th>
          <th class="text-left">Permisos</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="role in roles"
          :key="role.name"
        >
          <td class="font-mono text-body-2">
            {{ role.name }}
          </td>
          <td class="font-weight-medium">
            {{ role.label }}
          </td>
          <td>
            <div class="d-flex flex-wrap ga-1 align-center">
              <!-- Chips para permisos visibles -->
              <v-chip
                v-for="perm in role.permissions.slice(0, 6)"
                :key="perm"
                size="x-small"
                color="primary"
                variant="flat"
              >
                {{ perm }}
              </v-chip>

              <!-- Chip "+X más" clickeable -->
              <v-chip
                v-if="role.permissions.length > 6"
                size="small"
                color="info"
                variant="tonal"
                @click="showAllPermissions(role)"
                class="cursor-pointer"
              >
                +{{ role.permissions.length - 6 }} más
                <v-icon right size="14" class="ml-1">mdi-chevron-down</v-icon>
              </v-chip>
            </div>
          </td>
        </tr>
      </tbody>
    </v-table>

    <!-- Estado de carga / error / vacío -->
    <RequestState :loading="loading" :error-message="errorMessage" />

    <v-alert
      v-if="!loading && !errorMessage && roles.length === 0"
      type="info"
      variant="tonal"
      class="mt-4"
    >
      No hay roles disponibles.
    </v-alert>

    <!-- Diálogo para mostrar todos los permisos -->
    <v-dialog v-model="dialog" max-width="600px">
      <v-card v-if="selectedRole">
        <v-card-title class="d-flex justify-space-between align-center">
          <div>
            <span class="text-h6">{{ selectedRole.label }}</span>
            <span class="text-caption text-grey ml-2 font-mono">({{ selectedRole.name }})</span>
          </div>
          <v-btn icon="mdi-close" variant="text" @click="dialog = false"></v-btn>
        </v-card-title>

        <v-divider></v-divider>

        <v-card-text class="mt-4">
          <v-chip-group column>
            <v-chip
              v-for="permission in selectedRole.permissions"
              :key="permission"
              color="primary"
              variant="flat"
              size="small"
              class="mb-2"
            >
              {{ permission }}
            </v-chip>
          </v-chip-group>
        </v-card-text>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="primary" variant="text" @click="dialog = false">
            Cerrar
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </FeaturePanel>
</template>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}
</style>
