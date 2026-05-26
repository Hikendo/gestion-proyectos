import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import * as projectsService from '@/services/projects.service';
import type { ProjectI, ProjectErroresFormI } from '@/interfaces/ProjectI';

const emptyForm = (): ProjectI => ({
    id: 0, name: '', status: 'planning', owner_id: 0,
    code: null, description: null, start_date: null, end_date: null,
    budget: null, progress: null,
});
const emptyErrors = (): ProjectErroresFormI => ({
    name: [], status: [], owner_id: [],
});

export function useProjects() {
    const router = useRouter();
    const appStore = useAppStore();
    const { snackbar } = storeToRefs(appStore);

    const form = ref<ProjectI>(emptyForm());
    const errores = ref<ProjectErroresFormI>(emptyErrors());

    async function handleStore() {
        const response = await projectsService.store(form.value);
        if (response.status) {
            snackbar.value = { show: true, text: 'Proyecto creado', color: 'success' };
            router.push({ name: 'projects' });
        } else {
            if ('errors' in response && response.errors) errores.value = response.errors as ProjectErroresFormI;
            snackbar.value = { show: true, text: response.message, color: 'error' };
        }
    }

    async function handleUpdate() {
        const response = await projectsService.update(form.value.id, form.value);
        if (response.status) {
            snackbar.value = { show: true, text: 'Proyecto actualizado', color: 'success' };
        } else {
            if ('errors' in response && response.errors) errores.value = response.errors as ProjectErroresFormI;
            snackbar.value = { show: true, text: response.message, color: 'error' };
        }
    }

    return { form, errores, handleStore, handleUpdate };
}
