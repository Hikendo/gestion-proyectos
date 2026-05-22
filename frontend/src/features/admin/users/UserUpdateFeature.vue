<script setup lang="ts">
import { computed, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import FeaturePanel from '../../../components/FeaturePanel.vue';
import RequestState from '../../../components/RequestState.vue';
import UserForm from './UserForm.vue';
import { useUserUpdate } from '../../../composables/useUserUpdate';
import { useRolesList } from '../../../composables/useRolesList';
import { useConfirmAction } from '../../../composables/useConfirmAction';

const route = useRoute();
const { confirmAction } = useConfirmAction();
const { form, errors, isLoading, successMessage, handleUpdate, loadUser, usersService } = useUserUpdate();
const { roles, loadRoles } = useRolesList();

const userId = computed(() => {
    const id = route.query.id;
    return typeof id === 'string' ? Number(id) : null;
});

async function submitUpdate(): Promise<void> {
    if (!userId.value) return;

    const shouldContinue = confirmAction({
        message: `Vas a actualizar el usuario con ID ${userId.value}. Deseas continuar?`,
    });

    if (!shouldContinue) {
        return;
    }

    await handleUpdate(userId.value);
}

onMounted(async () => {
    await loadRoles();
    if (userId.value) {
        await loadUser(userId.value);
    }
});

watch(() => route.query.id, async () => {
    if (userId.value) {
        await loadUser(userId.value);
    }
});
</script>

<template>
    <FeaturePanel title="Actualizar usuario" description="Edita los datos de un usuario existente por ID.">
        <p v-if="!userId" class="request-state__error">
            Debes entrar desde la tabla de usuarios para editar un registro.
        </p>

        <form v-else @submit.prevent="submitUpdate">
            <input :value="userId" type="hidden" readonly>
            <UserForm
                :form="form"
                :errores="errors"
                :roles="roles"
                :mostrar-confirmacion="false"
                :is-loading="isLoading"
                submit-button-text="Actualizar usuario"
            />
        </form>

        <RequestState
            :loading="isLoading"
            :error-message="usersService.errorMessage"
            :success-message="successMessage"
        />
    </FeaturePanel>
</template>
