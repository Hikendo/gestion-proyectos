<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import * as membersService from '@/services/project-members.service';

const route  = useRoute();
const router = useRouter();
const appStore = useAppStore();
const { loader, snackbar } = storeToRefs(appStore);
const projectId = () => Number(route.params.projectId);

const form = ref({ user_id: 0, role: 'developer' });
const errors = ref<any>({});
const roles = ['manager','developer','qa','support','client'];
const userName = ref('');

onMounted(async () => {
    loader.value = true;
    const id = Number(route.params.id);
    const response = await membersService.index(projectId());
    if (response.status && response.items) {
        const list: any[] = (response.items as any).data ?? (Array.isArray(response.items) ? response.items : []);
        const member = list.find((m: any) => m.id === id);
        if (member) {
            form.value = { user_id: member.user_id, role: member.role };
            userName.value = member.user?.name ?? `Usuario #${member.user_id}`;
        }
    }
    loader.value = false;
});

async function handleUpdate() {
    errors.value = {};
    loader.value = true;
    const response = await membersService.store(projectId(), form.value as any);
    if (response.status) {
        snackbar.value = { show: true, text: 'Miembro actualizado', color: 'success' };
        router.push({ name: 'members', params: { projectId: projectId() } });
    } else {
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
              <h4 class="text-h4">Editar <strong>Miembro</strong></h4>
              <VBtn variant="outlined" :to="{ name: 'members', params: { projectId: route.params.projectId } }" prepend-icon="ri-arrow-left-line">Volver</VBtn>
            </div>
          </VCardTitle>
        </VCardItem>
      </VCard>
    </VCol>
    <VCol cols="12" md="6">
      <VCard>
        <VCardText>
          <VForm @submit.prevent="requestSave(handleUpdate)">
            <VTextField
              :model-value="userName"
              label="Usuario"
              variant="outlined"
              readonly
              class="mb-3"
            />
            <VSelect v-model="form.role" label="Rol en el proyecto"
              :items="roles" variant="outlined" :error-messages="errors.role" class="mb-4" />
            <VBtn type="submit" color="primary" block :loading="loader">Guardar cambios</VBtn>
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
