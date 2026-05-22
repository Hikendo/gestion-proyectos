<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import FeaturePanel from '../../../components/FeaturePanel.vue';
import RequestState from '../../../components/RequestState.vue';
import { useUsersService } from '../../../composables';

const { call, loading, errorMessage } = useUsersService();
const users = ref([]);
const search = ref('');

async function loadUsers() {
    const query = search.value ? { search: search.value } : undefined;
    const response = await call('list', query);

    if (response) {
        users.value = response.data;
    }
}

onMounted(loadUsers);

function formatRoles(user) {
    return user.roles?.length ? user.roles.join(', ') : 'Sin rol';
}

function formatDate(dateValue) {
    if (!dateValue) {
        return '-';
    }

    const date = new Date(dateValue);

    if (Number.isNaN(date.getTime())) {
        return '-';
    }

    return new Intl.DateTimeFormat('es-ES', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }).format(date);
}
</script>

<template>
    <FeaturePanel title="Listar usuarios" description="Consulta y filtra el listado de usuarios.">
        <div class="inline-fields">
            <label>
                <span>Buscar por nombre o email</span>
                <input v-model="search" type="text" placeholder="Buscar usuario">
            </label>
        </div>

        <div class="form-actions">
            <button type="button" class="button primary" :disabled="loading" @click="loadUsers">Buscar</button>
            <RouterLink class="button secondary" :to="{ name: 'users-create' }">Crear usuario</RouterLink>
        </div>

        <div v-if="users.length" class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Roles</th>
                        <th>Fecha de alta</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in users" :key="user.id">
                        <td>{{ user.id }}</td>
                        <td>{{ user.name || '-' }}</td>
                        <td>{{ user.email || '-' }}</td>
                        <td>{{ formatRoles(user) }}</td>
                        <td>{{ formatDate(user.created_at) }}</td>
                        <td>
                            <div class="table-actions">
                                <RouterLink
                                    class="icon-action icon-action--edit"
                                    :to="{ name: 'users-update', query: { id: String(user.id) } }"
                                    title="Editar usuario"
                                    aria-label="Editar usuario"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M4 20h4l10-10-4-4L4 16v4z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M12 6l4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </RouterLink>

                                <RouterLink
                                    class="icon-action icon-action--delete"
                                    :to="{ name: 'users-delete', query: { id: String(user.id) } }"
                                    title="Eliminar usuario"
                                    aria-label="Eliminar usuario"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M4 7h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                                        <path d="M9 7V5h6v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M7 7l1 12h8l1-12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M10 11v5M14 11v5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                                    </svg>
                                </RouterLink>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p v-else-if="!loading" class="feature-copy">No hay usuarios para mostrar.</p>

        <RequestState :loading="loading" :error-message="errorMessage" />
    </FeaturePanel>
</template>
