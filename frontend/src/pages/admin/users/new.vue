<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { useUserCreate } from '@/composables/useUserCreate';

const router   = useRouter();
const appStore = useAppStore();
const { snackbar } = storeToRefs(appStore);

const { form, errors, isLoading, handleCreate } = useUserCreate();

const confirmVisible = ref(false);
const pendingAction  = ref<(() => Promise<void>) | null>(null);

function requestSave(action: () => Promise<void>) {
    pendingAction.value = action;
    confirmVisible.value = true;
}
async function confirmAction() {
    confirmVisible.value = false;
    if (pendingAction.value) {
        const ok = await (pendingAction.value as () => Promise<boolean>)();
        if (ok) {
            snackbar.value = { show: true, text: 'Usuario creado correctamente', color: 'success' };
            router.push({ name: 'admin-users' });
        }
    }
    pendingAction.value = null;
}
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardItem>
          <VCardTitle>
            <div class="d-flex justify-space-between">
              <h4 class="text-h4">Crear usuario</h4>
              <VBtn variant="outlined" :to="{ name: 'admin-users' }" prepend-icon="ri-arrow-left-line">Volver</VBtn>
            </div>
          </VCardTitle>
        </VCardItem>
      </VCard>
    </VCol>
    <VCol cols="12" md="6">
      <VCard>
        <VCardText>
          <VForm @submit.prevent="requestSave(handleCreate)">
            <VTextField v-model="form.name" label="Nombre" variant="outlined"
              :error-messages="errors.name" class="mb-3" />
            <VTextField v-model="form.email" label="Email" type="email" variant="outlined"
              :error-messages="errors.email" class="mb-3" />
            <VTextField v-model="form.password" label="Contraseña" type="password" variant="outlined"
              :error-messages="errors.password" class="mb-3" />
            <VTextField v-model="form.password_confirmation" label="Confirmar contraseña" type="password"
              variant="outlined" :error-messages="errors.password_confirmation" class="mb-3" />
            <VSelect
              v-model="form.role"
              label="Rol global"
              variant="outlined"
              :items="[
                { title: 'Sin rol global', value: '' },
                { title: 'Project Manager', value: 'project-manager' },
                { title: 'Super Admin', value: 'super-admin' },
              ]"
              :error-messages="errors.role"
              class="mb-4"
            />
            <VBtn type="submit" color="primary" block :loading="isLoading">Crear usuario</VBtn>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>

    <VDialog v-model="confirmVisible" persistent max-width="400">
      <VCard>
        <VCardTitle class="text-h6 pt-4 px-6">Confirmar acción</VCardTitle>
        <VCardText class="px-6">¿Deseas crear este usuario?</VCardText>
        <VCardActions class="px-6 pb-4 justify-end">
          <VBtn variant="outlined" @click="confirmVisible = false">Cancelar</VBtn>
          <VBtn color="primary" variant="flat" @click="confirmAction">Confirmar</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </VRow>
</template>
