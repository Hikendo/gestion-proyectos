<script setup lang="ts">
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { useAuthStore } from '@/store/useAuthStore';
import * as authService from '@/services/auth.service';

const route  = useRoute();
const router = useRouter();
const appStore  = useAppStore();
const authStore = useAuthStore();
const { loader } = storeToRefs(appStore);

const form = ref({ email: '', password: '' });
const errors = ref<{ email?: string[]; password?: string[] }>({});
const showPassword = ref(false);

async function handleLogin() {
    errors.value = {};
    loader.value = true;

    const response = await authService.login(form.value);

    if (response.status && response.items) {
        authStore.setSession(response.items.user, response.items.token);
        const redirect = typeof route.query.redirect === 'string'
            ? route.query.redirect
            : '/dashboard';
        router.push(redirect);
    } else {
        if ('errors' in response && (response as any).errors?.errors) {
            errors.value = (response as any).errors.errors;
        }
        appStore.showError(response.message);
    }

    loader.value = false;
}
</script>

<template>
  <VApp>
    <VMain class="d-flex align-center justify-center"
      style="min-height: 100vh; background: rgba(var(--v-theme-surface-variant), 0.3);">
      <VCard width="440" class="pa-2" elevation="4">

        <VCardItem class="pt-8 px-6 pb-2 text-center">
          <VIcon icon="mdi-chart-gantt" size="52" color="primary" class="mb-2" />
          <VCardTitle class="text-h5 font-weight-bold">Gestión de Proyectos</VCardTitle>
          <VCardSubtitle class="pb-1">Ingresa con tus credenciales para continuar</VCardSubtitle>
        </VCardItem>

        <VCardText class="px-6 pb-8">
          <VForm @submit.prevent="handleLogin">
            <VTextField
              v-model="form.email"
              label="Correo electrónico"
              type="email"
              prepend-inner-icon="mdi-email-outline"
              variant="outlined"
              density="comfortable"
              :error-messages="errors.email"
              class="mb-3"
              autocomplete="email"
              autofocus
            />
            <VTextField
              v-model="form.password"
              label="Contraseña"
              :type="showPassword ? 'text' : 'password'"
              prepend-inner-icon="mdi-lock-outline"
              :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
              @click:append-inner="showPassword = !showPassword"
              variant="outlined"
              density="comfortable"
              :error-messages="errors.password"
              class="mb-4"
              autocomplete="current-password"
            />

            <VBtn
              type="submit"
              color="primary"
              block
              size="large"
              :loading="loader"
              prepend-icon="mdi-login"
            >
              Iniciar sesión
            </VBtn>
          </VForm>

          <div class="text-center mt-4">
            <RouterLink :to="{ name: 'forgot-password' }"
              class="text-primary text-decoration-none text-body-2">
              ¿Olvidaste tu contraseña?
            </RouterLink>
          </div>
        </VCardText>

      </VCard>
    </VMain>
  </VApp>
</template>
