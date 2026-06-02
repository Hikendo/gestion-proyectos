<script setup lang="ts">
import { computed, onMounted } from 'vue';
import FeaturePanel from '../../../components/FeaturePanel.vue';
import RequestState from '../../../components/RequestState.vue';
import UserForm from './UserForm.vue';
import { useUserCreate } from '../../../composables/useUserCreate';
import { useRoles } from '../../../composables/useRolesList';

const { form, errors, isLoading, successMessage, handleCreate, usersService } = useUserCreate();
const { roles, loadRoles } = useRoles();

const errorMessage = computed(() => '');

onMounted(loadRoles);
</script>

<template>
    <FeaturePanel title="Crear usuario" description="Registra un nuevo usuario en el sistema con rol obligatorio.">
        <form @submit.prevent="handleCreate">
            <UserForm
                :form="form"
                :errores="errors"
                :roles="roles"
                :mostrar-confirmacion="true"
                :is-loading="isLoading"
                submit-button-text="Crear usuario"
            />
        </form>

        <RequestState
            :loading="isLoading"
            :error-message="errorMessage"
            :success-message="successMessage"
        />
    </FeaturePanel>
</template>
