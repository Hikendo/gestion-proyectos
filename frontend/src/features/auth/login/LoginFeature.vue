<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import FeaturePanel from '../../../components/FeaturePanel.vue';
import RequestState from '../../../components/RequestState.vue';
import ValidationErrors from '../../../components/ValidationErrors.vue';
import { useAuthService } from '../../../composables';

const route = useRoute();
const router = useRouter();
const { call, loading, errorMessage, validationErrors } = useAuthService();
const successMessage = ref('');
const form = reactive({
    email: 'admin@admin.com',
    password: 'Admin1234!',
});

async function handleLogin() {
    successMessage.value = '';
    const response = await call('login', { ...form });

    if (response) {
        successMessage.value = 'Sesión iniciada y token guardado en localStorage.';

    const redirectTarget = typeof route.query.redirect === 'string' ? route.query.redirect : '/dashboard';
    await router.push(redirectTarget);
    }
}
</script>

<template>
  <FeaturePanel title="Auth / Login" description="Consume el endpoint de autenticación y expone errores por campo para el formulario.">
    <form class="form-grid" @submit.prevent="handleLogin">
      <label>
        <span>Email</span>
        <input v-model="form.email" type="email" placeholder="admin@admin.com">
        <ValidationErrors :errors="validationErrors.email || []" />
      </label>

      <label>
        <span>Password</span>
        <input v-model="form.password" type="password" placeholder="••••••••">
        <ValidationErrors :errors="validationErrors.password || []" />
      </label>

      <button class="button primary" :disabled="loading" type="submit">Iniciar sesión</button>
    </form>

    <RequestState :loading="loading" :error-message="errorMessage" :success-message="successMessage" />
  </FeaturePanel>
</template>
