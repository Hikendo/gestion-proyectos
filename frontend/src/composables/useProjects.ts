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

function buildFormData(data: Record<string, any>, files: File[]): FormData {
    const formData = new FormData();
    for (const [key, value] of Object.entries(data)) {
        if (value !== null && value !== undefined && key !== 'progress') {
            formData.append(key, String(value));
        }
    }
    for (const file of files) {
        formData.append('attachments[]', file);
    }
    return formData;
}

export function useProjects() {
    const router = useRouter();
    const appStore = useAppStore();
    const { snackbar } = storeToRefs(appStore);

    const form = ref<ProjectI>(emptyForm());
    const errores = ref<ProjectErroresFormI>(emptyErrors());

    async function handleStore(files?: File[]) {
        const { progress, id, owner_id, ...formFields } = { ...form.value };
        const payload = files?.length
            ? buildFormData(formFields, files)
            : formFields;

        const response = await projectsService.store(payload);
        if (response.status) {
            snackbar.value = { show: true, text: 'Proyecto creado', color: 'success' };
            router.push({ name: 'projects' });
        } else {
            if ('errors' in response && response.errors) errores.value = response.errors as ProjectErroresFormI;
            snackbar.value = { show: true, text: response.message, color: 'error' };
        }
    }

    async function handleUpdate(files?: File[]) {
        const { progress, id, owner_id, ...formFields } = { ...form.value };
        const payload = files?.length
            ? buildFormData(formFields, files)
            : formFields;

        const response = await projectsService.update(form.value.id, payload);
        if (response.status) {
            snackbar.value = { show: true, text: 'Proyecto actualizado', color: 'success' };
            router.push({ name: 'project-detail', params: { projectId: form.value.id } })

        } else {
            if ('errors' in response && response.errors) errores.value = response.errors as ProjectErroresFormI;
            snackbar.value = { show: true, text: response.message, color: 'error' };
        }
    }

    return { form, errores, handleStore, handleUpdate };
}
