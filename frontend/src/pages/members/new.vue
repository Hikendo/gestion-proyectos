<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import * as membersService from '@/services/project-members.service';
import * as usersService from '@/services/users.service';

const route  = useRoute();
const router = useRouter();
const appStore = useAppStore();
const { loader, snackbar } = storeToRefs(appStore);
const projectId = () => Number(route.params.projectId);

const form = ref({ user_id: null as number | null, role: 'developer' });
const errors = ref<any>({});
const roles = ['manager','developer','qa','support','client'];

type UserOption = { id: number; name: string; email: string };
const users = ref<UserOption[]>([]);
const usersLoading = ref(false);

onMounted(async () => {
    usersLoading.value = true;
    const response = await usersService.all();
    if (response.status && response.items) users.value = response.items as UserOption[];
    usersLoading.value = false;
});

async function handleStore() {
    errors.value = {};
    loader.value = true;
    const response = await membersService.store(projectId(), form.value as any);
    if (response.status) {
        snackbar.value = { show: true, text: 'Miembro agregado', color: 'success' };
        router.push({ name: 'members', params: { projectId: projectId() } });
    } else {
        if ('errors' in response && response.errors) errors.value = response.errors;
        snackbar.value = { show: true, text: response.message, color: 'error' };
    }
    loader.value = false;
}

const confirmVisible = ref(false);
const pendingAction   = ref<(() => Promise<void>) | null>(null);

function requestSave(action: () => Promise<void>) {
    pendingAction.value = action;
    confirmVisible.value = true;
}

async function confirmAction() {
    confirmVisible.value = false;
    if (pendingAction.value) await pendingAction.value();
    pendingAction.value = null;
}
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardItem>
          <VCardTitle>
            <div class="d-flex justify-space-between flex-wrap">
              <h4 class="text-h4">Agregar <strong>Miembro</strong></h4>
              <VBtn variant="outlined" :to="{ name: 'members', params: { projectId: route.params.projectId } }" prepend-icon="mdi-arrow-left">Volver</VBtn>
            </div>
          </VCardTitle>
        </VCardItem>
      </VCard>
    </VCol>
    <VCol cols="12" md="6">
      <VCard>
        <VCardText>
          <VForm @submit.prevent="requestSave(handleStore)">
            <VAutocomplete
              v-model="form.user_id"
              :items="users"
              item-title="name"
              item-value="id"
              :item-props="(item: any) => ({ title: item.name, subtitle: item.email })"
              label="Usuario"
              variant="outlined"
              :loading="usersLoading"
              :error-messages="errors.user_id"
              eager
              class="mb-3"
              clearable
              no-data-text="Sin resultados"
            />
            <VSelect v-model="form.role" label="Rol en el proyecto"
              :items="roles" variant="outlined" :error-messages="errors.role" class="mb-4" />
            <VBtn type="submit" color="primary" block :loading="loader">Agregar miembro</VBtn>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>

    <VDialog v-model="confirmVisible" persistent max-width="400">
      <VCard>
        <VCardTitle class="text-h6">Confirmar acción</VCardTitle>
        <VCardText>¿Deseas guardar los cambios?</VCardText>
        <VCardActions class="justify-end gap-2 pb-4 px-4">
          <VBtn variant="outlined" @click="confirmVisible = false">Cancelar</VBtn>
          <VBtn color="primary" variant="flat" @click="confirmAction">Confirmar</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </VRow>
</template>
