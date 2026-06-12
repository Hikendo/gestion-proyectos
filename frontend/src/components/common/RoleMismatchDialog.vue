<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';

/**
 * Diálogo persistente que se muestra cuando el backend detecta
 * que el rol del usuario cambió (409 ROLE_MISMATCH).
 *
 * El usuario debe recargar la página para obtener los permisos del nuevo rol.
 */

const visible = ref(false);

function handleRoleMismatch() {
    visible.value = true;
}

function reloadPage() {
    window.location.reload();
}

onMounted(() => {
    window.addEventListener('auth:role-mismatch', handleRoleMismatch);
});

onUnmounted(() => {
    window.removeEventListener('auth:role-mismatch', handleRoleMismatch);
});
</script>

<template>
    <VDialog v-model="visible" persistent max-width="480">
        <VCard>
            <VCardItem>
                <template #prepend>
                    <VAvatar color="warning" variant="tonal" size="48" rounded="lg">
                        <VIcon icon="ri-error-warning-line" size="28" />
                    </VAvatar>
                </template>
                <VCardTitle class="text-wrap">
                    Permisos actualizados
                </VCardTitle>
                <VCardSubtitle class="text-wrap text-medium-emphasis mt-1">
                    Tus permisos de acceso han sido modificados por un administrador.
                    Es necesario recargar la página para continuar con los permisos actualizados.
                </VCardSubtitle>
            </VCardItem>

            <VDivider />

            <VCardActions class="justify-end ga-2 pa-4">
                <VBtn color="warning" variant="elevated" prepend-icon="ri-refresh-line" @click="reloadPage">
                    Recargar ahora
                </VBtn>
            </VCardActions>
        </VCard>
    </VDialog>
</template>