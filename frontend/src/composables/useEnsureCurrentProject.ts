import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAuthStore } from '@/store/useAuthStore';
import { useAppStore } from '@/store/useAppStore';
import { usePermissionStore } from '@/store/usePermissionStore';
import * as projectsService from '@/services/projects.service';
import { apiWithToken } from '@/services/http';
import type { ProjectI } from '@/interfaces/ProjectI';

/**
 * Garantiza que `currentProject` esté cargado en el store.
 * Úsalo en todas las páginas que dependen del proyecto activo
 * (edit, tasks, tickets, risks, etc.) para que el menú lateral
 * persista al navegar directamente a la URL o recargar la página.
 *
 * Además, inyecta los permisos del proyecto en PermissionStore
 * para que `canAction()` funcione para usuarios sin rol global
 * (developers, QA, etc. con permisos solo a nivel proyecto).
 *
 * @param withLoader  Si es true (default: false), activa el loader global
 *                    durante la carga. Ponlo en false si la página padre ya
 *                    controla el loader.
 */
export function useEnsureCurrentProject(withLoader = false) {
    const route     = useRoute();
    const router    = useRouter();
    const authStore = useAuthStore();
    const appStore  = useAppStore();
    const permissionStore = usePermissionStore();
    const { currentProject } = storeToRefs(authStore);

    async function loadProjectPermissions(projectId: number): Promise<void> {
        try {
            const { data } = await apiWithToken.get<{
                status: boolean;
                items: { permissions?: string[] };
            }>(`/projects/${encodeURIComponent(projectId)}/permissions`);
            if (data.status && data.items?.permissions) {
                permissionStore.setProjectPermissions(data.items.permissions);
            }
        } catch {
            // Si falla, el usuario igual puede usar el sistema — el backend
            // aplica las policies reales como fuente de verdad.
        }
    }

    async function ensureProject(): Promise<ProjectI | null> {
        const projectId = Number(route.params.projectId);
        if (!projectId) return null;

        // Ya está cargado y coincide con el projectId de la ruta.
        // Aun así, recargamos los permisos del proyecto por si vienen de
        // una navegación entre proyectos o el store se limpió parcialmente.
        if (currentProject.value?.id === projectId) {
            await loadProjectPermissions(projectId);
            return currentProject.value;
        }

        if (withLoader) appStore.loader = true;
        const response = await projectsService.show(projectId);
        if (withLoader) appStore.loader = false;

        if (response.status && response.items) {
            authStore.setCurrentProject(response.items as ProjectI);
            // Cargar permisos del proyecto para el usuario actual
            await loadProjectPermissions(projectId);
            return response.items as ProjectI;
        }

        appStore.snackbar = { show: true, text: 'Proyecto no encontrado', color: 'error' };
        router.push({ name: 'projects' });
        return null;
    }

    onMounted(ensureProject);

    return { ensureProject };
}
