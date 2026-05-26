import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import * as milestonesService from '@/services/project-milestones.service';
import type { MilestoneI, MilestoneErroresFormI } from '@/interfaces/MilestoneI';

const emptyForm = (): MilestoneI => ({
    id: 0, project_id: 0, title: '', completed: false, target_date: null,
});
const emptyErrors = (): MilestoneErroresFormI => ({
    project_id: [], title: [], completed: [],
});

export function useMilestones() {
    const router = useRouter();
    const route = useRoute();
    const appStore = useAppStore();
    const { snackbar } = storeToRefs(appStore);

    const form = ref<MilestoneI>(emptyForm());
    const errores = ref<MilestoneErroresFormI>(emptyErrors());

    const projectId = () => Number(route.params.projectId);

    async function handleStore() {
        const response = await milestonesService.store(projectId(), form.value);
        if (response.status) {
            snackbar.value = { show: true, text: 'Hito creado', color: 'success' };
            router.push({ name: 'milestones', params: { projectId: projectId() } });
        } else {
            if ('errors' in response && response.errors) errores.value = response.errors as MilestoneErroresFormI;
            snackbar.value = { show: true, text: response.message, color: 'error' };
        }
    }

    async function handleUpdate() {
        const response = await milestonesService.update(projectId(), form.value.id, form.value);
        if (response.status) {
            snackbar.value = { show: true, text: 'Hito actualizado', color: 'success' };
            router.push({ name: 'milestones', params: { projectId: projectId() } });
        } else {
            if ('errors' in response && response.errors) errores.value = response.errors as MilestoneErroresFormI;
            snackbar.value = { show: true, text: response.message, color: 'error' };
        }
    }

    return { form, errores, handleStore, handleUpdate };
}
