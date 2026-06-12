<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useUserUpdate } from '@/composables/useUserUpdate';

const route = useRoute();
const router = useRouter();
const userId = Number(route.params.id);

const { form, errors, isLoading, loadUser, handleUpdate, availablePermissions, rolePermissions, fetchPermissions, togglePermission } = useUserUpdate();

const confirmVisible = ref(false);
const pendingAction = ref<(() => Promise<void>) | null>(null);
const notFound = ref(false);

/** Rol original al cargar la página, para detectar cambios en el dropdown. */
const originalRole = ref('');
/** Se activa después de que loadUser terminó de hidratar el formulario. */
const initialLoadDone = ref(false);

onMounted(async () => {
  const ok = await loadUser(userId);
  if (!ok) notFound.value = true;
  originalRole.value = form.role;
  initialLoadDone.value = true;
  await fetchPermissions();
});

/**
 * Cuando el admin cambia el rol en el dropdown, limpiamos los permisos
 * directos seleccionados. El nuevo rol debe definir sus propios permisos.
 * Así se evita que los permisos del rol anterior se filtren al payload.
 */
watch(
  () => form.role,
  (newRole, oldRole) => {
    if (initialLoadDone.value && oldRole !== undefined && newRole !== originalRole.value) {
      form.permissions = [];
    }
  },
);

function requestSave(action: () => Promise<void>) {
  pendingAction.value = action;
  confirmVisible.value = true;
}

async function confirmAction() {
  confirmVisible.value = false;
  if (pendingAction.value) await pendingAction.value();
  pendingAction.value = null;
  router.push({ name: 'admin-users' });
}
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardItem>
          <VCardTitle>
            <div class="d-flex justify-space-between flex-wrap">
              <h4 class="text-h4 text-wrap">Editar <strong>usuario</strong></h4>
              <VBtn variant="outlined" :to="{ name: 'admin-users' }" prepend-icon="ri-arrow-left-line">
                Volver
              </VBtn>
            </div>
          </VCardTitle>
        </VCardItem>
      </VCard>
    </VCol>

    <VCol v-if="notFound" cols="12">
      <VAlert type="error">Usuario no encontrado.</VAlert>
    </VCol>

    <VCol v-else cols="12" md="6">
      <VCard :loading="isLoading">
        <VCardText>
          <VForm @submit.prevent="requestSave(() => handleUpdate(userId))">
            <VTextField v-model="form.name" label="Nombre" variant="outlined" :error-messages="errors.name"
              class="mb-3" />
            <VTextField v-model="form.email" label="Email" type="email" variant="outlined"
              :error-messages="errors.email" class="mb-3" />
            <VTextField v-model="form.password" label="Nueva contraseña" type="password" variant="outlined"
              :error-messages="errors.password" placeholder="Dejar vacío para no cambiar" class="mb-3" />
            <VTextField v-model="form.password_confirmation" label="Confirmar nueva contraseña" type="password"
              variant="outlined" :error-messages="errors.password_confirmation" class="mb-3" />
            <VSelect v-model="form.role" label="Rol global" variant="outlined" :items="[
              { title: 'Sin rol global', value: '' },
              { title: 'Project Manager', value: 'project-manager' },
              { title: 'Super Admin', value: 'super-admin' },
            ]" :error-messages="errors.role" class="mb-4" />

            <!-- Selector de permisos directos -->
            <div v-if="availablePermissions.length > 0" class="mb-4">
              <p class="text-subtitle-2 font-weight-bold mb-2">Permisos</p>
              <p class="text-caption text-medium-emphasis mb-2">
                <VIcon icon="ri-information-line" size="14" color="info" class="me-1" />
                <span class="me-3">
                  <VChip color="info" variant="flat" size="x-small" class="me-1" /> Rol
                </span>
                <VChip color="warning" variant="flat" size="x-small" class="me-1" /> Directo
              </p>
              <div class="d-flex flex-wrap gap-2">
                <VChip v-for="perm in availablePermissions" :key="perm.id" :color="form.permissions.includes(perm.name)
                  ? (rolePermissions.has(perm.name) ? 'info' : 'warning')
                  : 'grey-lighten-1'" :variant="form.permissions.includes(perm.name) ? 'flat' : 'tonal'"
                  class="cursor-pointer" @click="togglePermission(perm.name)">
                  {{ perm.name }}
                </VChip>
              </div>
            </div>

            <VBtn type="submit" color="primary" block :loading="isLoading">
              Guardar cambios
            </VBtn>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>

    <VDialog v-model="confirmVisible" persistent max-width="400">
      <VCard>
        <VCardTitle class="text-h6 pt-4 px-6">Confirmar acción</VCardTitle>
        <VCardText class="px-6">¿Deseas guardar los cambios en este usuario?</VCardText>
        <VCardActions class="px-6 pb-4 justify-end">
          <VBtn variant="outlined" @click="confirmVisible = false">Cancelar</VBtn>
          <VBtn color="primary" variant="flat" @click="confirmAction">Confirmar</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </VRow>
</template>
