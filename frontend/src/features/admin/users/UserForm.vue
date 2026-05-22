<script setup lang="ts">
import { PropType, computed } from 'vue';
import ValidationErrors from '../../../components/ValidationErrors.vue';
import type { UserFormI, UserValidationErrorsI } from '../../../composables/useUsers';

const props = defineProps<{
    form: UserFormI;
    errores: UserValidationErrorsI;
    roles: Array<{ name: string; label: string }>;
    mostrarConfirmacion?: boolean;
    isLoading?: boolean;
    submitButtonText?: string;
}>();

const passwordMatches = computed(() => {
    if (!props.mostrarConfirmacion) return true;
    return props.form.password === props.form.password_confirmation;
});

function getFieldErrors(fieldName: keyof UserValidationErrorsI): string[] {
    return props.errores[fieldName] || [];
}
</script>

<template>
    <div class="form-grid">
        <label>
            <span>Nombre</span>
            <input v-model="form.name" type="text" required>
            <ValidationErrors :errors="getFieldErrors('name')" />
        </label>

        <label>
            <span>Email</span>
            <input v-model="form.email" type="email" required>
            <ValidationErrors :errors="getFieldErrors('email')" />
        </label>

        <label>
            <span>Password</span>
            <input v-model="form.password" type="password" :required="mostrarConfirmacion">
            <ValidationErrors :errors="getFieldErrors('password')" />
        </label>

        <label v-if="mostrarConfirmacion">
            <span>Confirmar password</span>
            <input v-model="form.password_confirmation" type="password" required>
            <ValidationErrors :errors="getFieldErrors('password_confirmation')" />
        </label>

        <label>
            <span>Rol</span>
            <select v-model="form.role" required>
                <option value="" disabled>Selecciona un rol</option>
                <option v-for="role in roles" :key="role.name" :value="role.name">
                    {{ role.label }}
                </option>
            </select>
            <ValidationErrors :errors="getFieldErrors('role')" />
        </label>

        <button type="submit" class="button primary" :disabled="isLoading">
            {{ isLoading ? 'Procesando...' : submitButtonText || 'Guardar' }}
        </button>
    </div>
</template>
