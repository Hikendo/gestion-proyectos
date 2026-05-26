import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import * as blockersService from '@/services/project-blockers.service';
import type { BlockerI, BlockerErroresFormI } from '@/interfaces/BlockerI';

const emptyForm = (): BlockerI => ({
    id: 0, project_id: 0, title: '', severity: 'medium', resolved: false,
    task_id: null, description: null,
});
const emptyErrors = (): BlockerErroresFormI => ({
    project_id: [], title: [], severity: [], resolved: [],
});

export function useBlockers() {
    const router = useRouter();
    const route = useRoute();
    const appStore = useAppStore();
    const { snackbar } = storeToRefs(appStore);

    const form = ref<BlockerI>(emptyForm());
    const errores = ref<BlockerErroresFormI>(emptyErrors());

    const projectId = () => Number(route.params.projectId);

    async function handleStore() {
        const response = await blockersService.store(projectId(), form.value);
        if (response.status) {
            snackbar.value = { show: true, text: 'Bloqueador registrado', color: 'success' };
            router.push({ name: 'blockers', params: { projectId: projectId() } });
        } else {
            if ('errors' in response && response.errors) errores.value = response.errors as BlockerErroresFormI;
            snackbar.value = { show: true, text: response.message, color: 'error' };
        }
    }

    async function handleUpdate() {
        const response = await blockersService.update(projectId(), form.value.id, form.value);
        if (response.status) {
            snackbar.value = { show: true, text: 'Bloqueador actualizado', color: 'success' };
            router.push({ name: 'blockers', params: { projectId: projectId() } });
        } else {
            if ('errors' in response && response.errors) errores.value = response.errors as BlockerErroresFormI;
            snackbar.value = { show: true, text: response.message, color: 'error' };
        }
    }

    return { form, errores, handleStore, handleUpdate };
}
