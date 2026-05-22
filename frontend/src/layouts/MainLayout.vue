<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { authService } from '../services';

const route = useRoute();
const router = useRouter();

const menuSections = [
    {
        title: 'Core API',
        items: [
            { name: 'dashboard', label: 'Dashboard' },
        ],
    },
    {
        title: 'Administracion',
        items: [
            { name: 'users-list', label: 'Usuarios' },
            { name: 'roles', label: 'Roles' },
        ],
    },
    {
        title: 'Proyectos',
        items: [
            { name: 'projects', label: 'Proyectos' },
        ],
    },
];

const activePath = computed(() => route.path);

function isItemActive(itemName) {
    const path = activePath.value;

    if (itemName === 'users-list') {
        return path.startsWith('/users');
    }

    return route.name === itemName;
}

async function handleLogout() {
    try {
        await authService.logout();
    } catch {
        // Si falla el endpoint, igual cerramos sesion local.
    }

    await router.push({ name: 'login' });
}
</script>

<template>
    <div class="app-layout">
        <aside class="app-sidebar">
            <header class="app-sidebar__header">
                <p>Sistema</p>
                <h1>Gestion de Proyectos</h1>
            </header>

            <nav class="app-sidebar__nav" aria-label="Menu principal">
                <section v-for="section in menuSections" :key="section.title" class="app-sidebar__section">
                    <h2>{{ section.title }}</h2>
                    <RouterLink
                        v-for="item in section.items"
                        :key="item.name"
                        :to="{ name: item.name }"
                        class="app-sidebar__link"
                        :class="{ 'is-active': isItemActive(item.name) }"
                    >
                        {{ item.label }}
                    </RouterLink>
                </section>
            </nav>

            <button type="button" class="button danger app-sidebar__logout" @click="handleLogout">
                Cerrar sesion
            </button>
        </aside>

        <main class="app-main">
            <RouterView />
        </main>
    </div>
</template>
