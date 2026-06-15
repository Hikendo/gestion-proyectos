import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { getAuthToken, setAuthToken, clearAuthToken } from '@/services/http';
import type { UserI } from '@/interfaces/UserI';
import type { ProjectI } from '@/interfaces/ProjectI';
import type { ProjectMemberRole } from '@/interfaces/enums';
// 🔔 Importamos las utilidades de Firebase de forma asíncrona
import { requestNotificationPermission, listenForegroundNotifications, deleteFcmToken } from '@/services/firebase';
import { usePermissionStore } from '@/store/usePermissionStore';

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

        // Prime the PermissionStore with user's permissions from login/me response
        const permissionStore = usePermissionStore();
        if (user.permissions && Array.isArray(user.permissions)) {
            permissionStore.setPermissions(user.permissions);
        }

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
        const permissionStore = usePermissionStore();
        permissionStore.clearProjectPermissions();
    }

    async function clearSession() {
        // Destruir el token FCM del navegador para evitar conflictos.
        // Si otro usuario inicia sesión en el mismo navegador, Firebase
        // generará un token nuevo para esa cuenta.
        await deleteFcmToken();

        clearAuthToken();
        authUser.value = null;
        currentProject.value = null;
        currentProjectRole.value = null;
        const permissionStore = usePermissionStore();
        permissionStore.clearPermissions();
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