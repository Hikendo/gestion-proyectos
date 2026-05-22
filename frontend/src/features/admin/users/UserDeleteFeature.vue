<script setup lang="ts">
import { computed, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useRouter } from 'vue-router';
import FeaturePanel from '../../../components/FeaturePanel.vue';
import RequestState from '../../../components/RequestState.vue';
import { useUserDelete } from '../../../composables/useUserDelete';
import { useConfirmAction } from '../../../composables/useConfirmAction';

const route = useRoute();
const router = useRouter();
const { confirmAction } = useConfirmAction();
const { isLoading, successMessage, handleDelete, usersService } = useUserDelete();

const userId = computed(() => {
    const id = route.query.id;
    return typeof id === 'string' ? Number(id) : null;
});

async function submitDelete(): Promise<void> {
    if (!userId.value) return;

    const shouldContinue = confirmAction({
        message: `Se eliminara el usuario con ID ${userId.value}. Esta accion no se puede deshacer. Deseas continuar?`,
    });

    if (!shouldContinue) {
        return;
    }

    const success = await handleDelete(userId.value);

    if (success) {
        successMessage.value = `Usuario ${userId.value} eliminado correctamente.`;
        // Redirect after a short delay
        setTimeout(() => {
            router.push({ name: 'users-list' });
        }, 1000);
    }
}
</script>

<template>
    <FeaturePanel title="Eliminar usuario" description="Elimina un usuario por ID.">
        <form @submit.prevent="submitDelete">
            <input :value="userId" type="hidden" readonly>

            <p v-if="!userId" class="request-state__error">
                Debes entrar desde la tabla de usuarios para eliminar un registro.
            </p>

            <button type="submit" class="button danger" :disabled="isLoading || !userId">Eliminar usuario</button>
        </form>

        <RequestState :loading="isLoading" :error-message="usersService.errorMessage" :success-message="successMessage" />
    </FeaturePanel>
</template>
