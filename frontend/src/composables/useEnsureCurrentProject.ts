import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAuthStore } from '@/store/useAuthStore';
import { useAppStore } from '@/store/useAppStore';
import * as projectsService from '@/services/projects.service';
import type { ProjectI } from '@/interfaces/ProjectI';

/**
 * Garantiza que `currentProject` esté cargado en el store.
 * Úsalo en todas las páginas que dependen del proyecto activo
 * (edit, tasks, tickets, risks, etc.) para que el menú lateral
 * persista al navegar directamente a la URL o recargar la página.
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
    const { currentProject } = storeToRefs(authStore);

    async function ensureProject(): Promise<ProjectI | null> {
        const projectId = Number(route.params.projectId);
        if (!projectId) return null;

        // Ya está cargado y coincide con el projectId de la ruta
        if (currentProject.value?.id === projectId) {
            return currentProject.value;
        }

        if (withLoader) appStore.loader = true;
        const response = await projectsService.show(projectId);
        if (withLoader) appStore.loader = false;

        if (response.status && response.items) {
            authStore.setCurrentProject(response.items as ProjectI);
            return response.items as ProjectI;
        }

        appStore.snackbar = { show: true, text: 'Proyecto no encontrado', color: 'error' };
        router.push({ name: 'projects' });
        return null;
    }

    onMounted(ensureProject);

    return { ensureProject };
}
