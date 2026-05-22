<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthService } from '../composables';
import ValidationErrors from '../components/ValidationErrors.vue';
import RequestState from '../components/RequestState.vue';

const route = useRoute();
const router = useRouter();
const { call, loading, errorMessage, validationErrors } = useAuthService();

const form = reactive({
    email: '',
    password: '',
    remember: true,
});
const successMessage = ref('');

async function login() {
    successMessage.value = '';

    const response = await call('login', {
        email: form.email,
        password: form.password,
    });

    if (!response) {
        return;
    }

    successMessage.value = 'Sesion iniciada correctamente.';
    const redirectTarget = typeof route.query.redirect === 'string' ? route.query.redirect : '/dashboard';
    await router.push(redirectTarget);
}
</script>

<template>
    <main class="auth-shell">
        <section class="auth-card">
            <header class="auth-card__header">
                <p>Autenticacion</p>
                <h1>Ingresar al sistema</h1>
                <span>Usa tu correo y contrasena para continuar.</span>
            </header>

            <form class="form-grid" @submit.prevent="login">
                <label>
                    <span>Email</span>
                    <input v-model="form.email" type="email" autocomplete="email" required>
                    <ValidationErrors :errors="validationErrors.email || []" />
                </label>

                <label>
                    <span>Password</span>
                    <input v-model="form.password" type="password" autocomplete="current-password" required>
                    <ValidationErrors :errors="validationErrors.password || []" />
                </label>

                <label class="checkbox-field">
                    <input v-model="form.remember" type="checkbox">
                    <span>Recordarme</span>
                </label>

                <div class="form-actions">
                    <button type="submit" class="button primary" :disabled="loading">Login</button>
                    <RouterLink class="button secondary" :to="{ name: 'forgot-password' }">
                        Recuperar contrasena
                    </RouterLink>
                </div>
            </form>

            <RequestState :loading="loading" :error-message="errorMessage" :success-message="successMessage" />
        </section>
    </main>
</template>
