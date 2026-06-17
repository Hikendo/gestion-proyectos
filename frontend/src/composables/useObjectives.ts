import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import * as objectivesService from '@/services/project-objectives.service';
import type { ObjectiveI, ObjectiveErroresFormI } from '@/interfaces/ObjectiveI';

const emptyForm = (): ObjectiveI => ({
    id: 0, project_id: 0, phase_id: null, title: '', type: 'specific', completed: false, description: null,
});
const emptyErrors = (): ObjectiveErroresFormI => ({
    project_id: [], title: [], type: [], completed: [],
});

export function useObjectives() {
    const router = useRouter();
    const route = useRoute();
    const appStore = useAppStore();
    const { snackbar } = storeToRefs(appStore);

    const form = ref<ObjectiveI>(emptyForm());
    const errores = ref<ObjectiveErroresFormI>(emptyErrors());

    const projectId = () => Number(route.params.projectId);

    async function handleStore() {
        const response = await objectivesService.store(projectId(), form.value);
        if (response.status) {
            snackbar.value = { show: true, text: 'Objetivo creado', color: 'success' };
            router.push({ name: 'objectives', params: { projectId: projectId() } });
        } else {
            if ('errors' in response && response.errors) errores.value = response.errors as ObjectiveErroresFormI;
            snackbar.value = { show: true, text: response.message, color: 'error' };
        }
    }

    async function handleUpdate() {
        const response = await objectivesService.update(projectId(), form.value.id, form.value);
        if (response.status) {
            snackbar.value = { show: true, text: 'Objetivo actualizado', color: 'success' };
            router.push({ name: 'objectives', params: { projectId: projectId() } });
        } else {
            if ('errors' in response && response.errors) errores.value = response.errors as ObjectiveErroresFormI;
            snackbar.value = { show: true, text: response.message, color: 'error' };
        }
    }

    return { form, errores, handleStore, handleUpdate };
}
