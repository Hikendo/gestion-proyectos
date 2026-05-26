<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { useAuthStore } from '@/store/useAuthStore';
import * as usersService from '@/services/users.service';

const router    = useRouter();
const appStore  = useAppStore();
const authStore = useAuthStore();
const { loader, snackbar } = storeToRefs(appStore);
const { authUser } = storeToRefs(authStore);

const form = ref({
    name: authUser.value?.name ?? '',
    email: authUser.value?.email ?? '',
    password: '',
    password_confirmation: '',
});
const errors = ref<any>({});

async function handleUpdate() {
    errors.value = {};
    loader.value = true;
    const id = authUser.value?.id ?? 0;
    const payload = form.value.password
        ? { ...form.value }
        : { name: form.value.name, email: form.value.email };
    const response = await usersService.update(id, payload as any);
    if (response.status) {
        snackbar.value = { show: true, text: 'Perfil actualizado', color: 'success' };
        router.push({ name: 'profile' });
    } else {
        if ('errors' in response && response.errors) errors.value = response.errors;
        snackbar.value = { show: true, text: response.message, color: 'error' };
    }
    loader.value = false;
}
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardItem>
          <VCardTitle>
            <div class="d-flex justify-space-between">
              <h4 class="text-h4">Editar perfil</h4>
              <VBtn variant="outlined" :to="{ name: 'profile' }" prepend-icon="mdi-arrow-left">Volver</VBtn>
            </div>
          </VCardTitle>
        </VCardItem>
      </VCard>
    </VCol>
    <VCol cols="12" md="6">
      <VCard>
        <VCardText>
          <VForm @submit.prevent="handleUpdate">
            <VTextField v-model="form.name" label="Nombre" variant="outlined" :error-messages="errors.name" class="mb-3" />
            <VTextField v-model="form.email" label="Email" type="email" variant="outlined" :error-messages="errors.email" class="mb-3" />
            <VTextField v-model="form.password" label="Nueva contraseña (opcional)" type="password" variant="outlined" :error-messages="errors.password" class="mb-3" />
            <VTextField v-model="form.password_confirmation" label="Confirmar contraseña" type="password" variant="outlined" class="mb-4" />
            <VBtn type="submit" color="primary" block :loading="loader">Guardar cambios</VBtn>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>
