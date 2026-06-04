import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { getAuthToken, setAuthToken, clearAuthToken } from '@/services/http';
import type { UserI } from '@/interfaces/UserI';
import type { ProjectI } from '@/interfaces/ProjectI';
import type { ProjectMemberRole } from '@/interfaces/enums';
// 🔔 Importamos las utilidades de Firebase de forma asíncrona
import { requestNotificationPermission, listenForegroundNotifications } from '@/services/firebase';

export const useAuthStore = defineStore('auth', () => {
    const authUser = ref<UserI | null>(null);
    const currentProject = ref<ProjectI | null>(null);
    const currentProjectRole = ref<ProjectMemberRole | null>(null);

    const isAuthenticated = computed(() => !!getAuthToken() && !!authUser.value);

    const isSuperAdmin = computed(() =>
        authUser.value?.roles?.includes('super-admin') ?? false,
    );

    const isGlobalProjectManager = computed(() =>
        authUser.value?.roles?.includes('project-manager') ?? false,
    );

    const isProjectManager = computed(() =>
        isGlobalProjectManager.value || currentProjectRole.value === 'manager' || isSuperAdmin.value,
    );

    // 🚀 Modificamos setSession para que sea asíncrona
    async function setSession(user: UserI, token: string) {
        setAuthToken(token);
        authUser.value = user;

        // 🔔 El token ya está asegurado en las cabeceras de Axios.
        // Ahora es 100% seguro arrancar Firebase sin riesgo de lanzar un 401.
        try {
            await requestNotificationPermission();
            listenForegroundNotifications();
        } catch (error) {
            console.error('Error al inicializar el servicio de notificaciones:', error);
        }
    }

    function setCurrentProject(project: ProjectI, role?: ProjectMemberRole | null) {
        currentProject.value = project;
        currentProjectRole.value = role ?? null;
    }

    function clearCurrentProject() {
        currentProject.value = null;
        currentProjectRole.value = null;
    }

    function clearSession() {
        clearAuthToken();
        authUser.value = null;
        currentProject.value = null;
        currentProjectRole.value = null;
    }

    return {
        authUser,
        currentProject,
        currentProjectRole,
        isAuthenticated,
        isSuperAdmin,
        isGlobalProjectManager,
        isProjectManager,
        setSession,
        setCurrentProject,
        clearCurrentProject,
        clearSession,
    };
});