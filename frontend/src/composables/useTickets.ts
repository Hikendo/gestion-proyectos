import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import * as ticketsService from '@/services/tickets.service';
import { buildFormData } from '@/composables/useAttachments';
import { canAction } from '@/helpers/canAction';
import type { TicketI, TicketErroresFormI } from '@/interfaces/TicketI';
import type { TicketPayload } from '@/services/types';

const emptyForm = (): TicketI => ({
    id: 0, project_id: 0, subject: '', status: 'open', priority: 'medium',
    description: null, created_by: null, assigned_to: null,
});
const emptyErrors = (): TicketErroresFormI => ({
    project_id: [], subject: [], status: [], priority: [],
});

export function useTickets() {
    const router = useRouter();
    const route = useRoute();
    const appStore = useAppStore();
    const { snackbar } = storeToRefs(appStore);

    const form = ref<TicketI>(emptyForm());
    const errores = ref<TicketErroresFormI>(emptyErrors());

    const projectId = () => Number(route.params.projectId);

    // Construye el payload del formulario. Un usuario sin permiso de asignación
    // (p.ej. un cliente) NO debe enviar `assigned_to`: el backend lo rechazaría
    // o lo ignoraría. Lo omitimos para que el default (Project Manager) aplique.
    function buildPayload(): TicketPayload {
        const payload: TicketPayload = { ...form.value };
        if (!canAction('ticket.assign')) {
            delete payload.assigned_to;
        }
        return payload;
    }

    async function handleStore(files?: File[]) {
        const base = buildPayload();
        const payload = files?.length
            ? buildFormData(base as Record<string, unknown>, files)
            : base;

        const response = await ticketsService.store(projectId(), payload);
        if (response.status) {
            snackbar.value = { show: true, text: 'Ticket creado', color: 'success' };
            router.push({ name: 'tickets', params: { projectId: projectId() } });
        } else {
            if ('errors' in response && response.errors) errores.value = response.errors as TicketErroresFormI;
            snackbar.value = { show: true, text: response.message, color: 'error' };
        }
    }

    async function handleUpdate(files?: File[]) {
        const base = buildPayload();
        const payload = files?.length
            ? buildFormData(base as Record<string, unknown>, files)
            : base;

        const response = await ticketsService.update(projectId(), form.value.id, payload);
        if (response.status) {
            snackbar.value = { show: true, text: 'Ticket actualizado', color: 'success' };
            router.push({ name: 'tickets', params: { projectId: projectId() } });
        } else {
            if ('errors' in response && response.errors) errores.value = response.errors as TicketErroresFormI;
            snackbar.value = { show: true, text: response.message, color: 'error' };
        }
    }

    return { form, errores, handleStore, handleUpdate };
}