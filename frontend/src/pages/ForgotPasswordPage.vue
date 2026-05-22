<script setup>
import { reactive, ref } from 'vue';
import { requestJson, getApiErrorMessage } from '../services';

const form = reactive({
    email: '',
});

const loading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

async function sendResetLink() {
    loading.value = true;
    errorMessage.value = '';
    successMessage.value = '';

    try {
        await requestJson('/auth/forgot-password', {
            method: 'POST',
            body: { email: form.email },
        });

        successMessage.value = 'Se envio el enlace de restablecimiento al correo indicado.';
    } catch (error) {
        errorMessage.value = getApiErrorMessage(error);
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <main class="auth-shell">
        <section class="auth-card">
            <header class="auth-card__header">
                <p>Recuperacion de password</p>
                <h1>Restablecer acceso</h1>
                <span>Ingresa tu email y te enviaremos instrucciones para recuperar la cuenta.</span>
            </header>

            <form class="form-grid" @submit.prevent="sendResetLink">
                <label>
                    <span>Email</span>
                    <input v-model="form.email" type="email" autocomplete="email" required>
                </label>

                <div class="form-actions">
                    <button type="submit" class="button primary" :disabled="loading">Enviar enlace</button>
                    <RouterLink class="button secondary" :to="{ name: 'login' }">Volver a login</RouterLink>
                </div>
            </form>

            <p v-if="loading" class="request-state__loading">Enviando solicitud...</p>
            <p v-if="errorMessage" class="request-state__error">{{ errorMessage }}</p>
            <p v-if="successMessage" class="request-state__success">{{ successMessage }}</p>
        </section>
    </main>
</template>
