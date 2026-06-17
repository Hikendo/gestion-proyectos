import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import * as risksService from '@/services/project-risks.service';
import type { RiskI, RiskErroresFormI } from '@/interfaces/RiskI';

const emptyForm = (): RiskI => ({
    id: 0, project_id: 0, phase_id: null, title: '', impact: 'medium', probability: 'medium',
    status: 'active', description: null, mitigation_plan: null,
});
const emptyErrors = (): RiskErroresFormI => ({
    project_id: [], title: [], impact: [], probability: [],
});

export function useRisks() {
    const router = useRouter();
    const route = useRoute();
    const appStore = useAppStore();
    const { snackbar } = storeToRefs(appStore);

    const form = ref<RiskI>(emptyForm());
    const errores = ref<RiskErroresFormI>(emptyErrors());

    const projectId = () => Number(route.params.projectId);

    async function handleStore() {
        const response = await risksService.store(projectId(), form.value);
        if (response.status) {
            snackbar.value = { show: true, text: 'Riesgo registrado', color: 'success' };
            router.push({ name: 'risks', params: { projectId: projectId() } });
        } else {
            if ('errors' in response && response.errors) errores.value = response.errors as RiskErroresFormI;
            snackbar.value = { show: true, text: response.message, color: 'error' };
        }
    }

    async function handleUpdate() {
        const response = await risksService.update(projectId(), form.value.id, form.value);
        if (response.status) {
            snackbar.value = { show: true, text: 'Riesgo actualizado', color: 'success' };
            router.push({ name: 'risks', params: { projectId: projectId() } });
        } else {
            if ('errors' in response && response.errors) errores.value = response.errors as RiskErroresFormI;
            snackbar.value = { show: true, text: response.message, color: 'error' };
        }
    }

    return { form, errores, handleStore, handleUpdate };
}
