import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import * as tasksService from '@/services/project-tasks.service';
import { buildFormData } from '@/composables/useAttachments';
import type { TaskI, TaskErroresFormI } from '@/interfaces/TaskI';

const emptyForm = (): TaskI => ({
    id: 0, project_id: 0, title: '', status: 'pending',
    phase_id: null, assigned_to: null, created_by: null,
    description: null, priority: null, due_date: null,
    estimated_hours: null, worked_hours: null, progress: null,
});
const emptyErrors = (): TaskErroresFormI => ({
    project_id: [], title: [], status: [],
});

export function useTasks() {
    const router = useRouter();
    const route = useRoute();
    const appStore = useAppStore();
    const { snackbar } = storeToRefs(appStore);

    const form = ref<TaskI>(emptyForm());
    const errores = ref<TaskErroresFormI>(emptyErrors());

    const projectId = () => Number(route.params.projectId);

    async function handleStore(files?: File[]) {
        const payload = files?.length
            ? buildFormData({ ...form.value }, files)
            : { ...form.value };

        const response = await tasksService.store(projectId(), payload);
        if (response.status) {
            snackbar.value = { show: true, text: 'Tarea creada', color: 'success' };
            router.push({ name: 'tasks', params: { projectId: projectId() } });
        } else {
            if ('errors' in response && response.errors) errores.value = response.errors as TaskErroresFormI;
            snackbar.value = { show: true, text: response.message, color: 'error' };
        }
    }

    async function handleUpdate(files?: File[]) {
        const payload = files?.length
            ? buildFormData({ ...form.value }, files)
            : { ...form.value };

        const response = await tasksService.update(projectId(), form.value.id, payload);
        if (response.status) {
            snackbar.value = { show: true, text: 'Tarea actualizada', color: 'success' };
            router.push({ name: 'tasks', params: { projectId: projectId() } });
        } else {
            if ('errors' in response && response.errors) errores.value = response.errors as TaskErroresFormI;
            snackbar.value = { show: true, text: response.message, color: 'error' };
        }
    }

    return { form, errores, handleStore, handleUpdate };
}