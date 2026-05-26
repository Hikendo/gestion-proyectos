import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import * as deliverablesService from '@/services/project-deliverables.service';
import type { DeliverableI, DeliverableErroresFormI } from '@/interfaces/DeliverableI';

const emptyForm = (): DeliverableI => ({
    id: 0, project_id: 0, name: '', approved: false,
    description: null, delivery_date: null,
});
const emptyErrors = (): DeliverableErroresFormI => ({
    project_id: [], name: [], approved: [],
});

export function useDeliverables() {
    const router = useRouter();
    const route = useRoute();
    const appStore = useAppStore();
    const { snackbar } = storeToRefs(appStore);

    const form = ref<DeliverableI>(emptyForm());
    const errores = ref<DeliverableErroresFormI>(emptyErrors());

    const projectId = () => Number(route.params.projectId);

    async function handleStore() {
        const response = await deliverablesService.store(projectId(), form.value);
        if (response.status) {
            snackbar.value = { show: true, text: 'Entregable creado', color: 'success' };
            router.push({ name: 'deliverables', params: { projectId: projectId() } });
        } else {
            if ('errors' in response && response.errors) errores.value = response.errors as DeliverableErroresFormI;
            snackbar.value = { show: true, text: response.message, color: 'error' };
        }
    }

    async function handleUpdate() {
        const response = await deliverablesService.update(projectId(), form.value.id, form.value);
        if (response.status) {
            snackbar.value = { show: true, text: 'Entregable actualizado', color: 'success' };
            router.push({ name: 'deliverables', params: { projectId: projectId() } });
        } else {
            if ('errors' in response && response.errors) errores.value = response.errors as DeliverableErroresFormI;
            snackbar.value = { show: true, text: response.message, color: 'error' };
        }
    }

    return { form, errores, handleStore, handleUpdate };
}
