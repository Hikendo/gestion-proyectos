import { describe, it, expect, vi, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useMilestones } from '@/composables/useMilestones';
import { useAppStore } from '@/store/useAppStore';
import * as milestonesService from '@/services/project-milestones.service';

const mockPush = vi.fn();

vi.mock('vue-router', () => ({
    useRouter: () => ({ push: mockPush }),
    useRoute: () => ({ params: { projectId: '3' } }),
}));

describe('useMilestones', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('form tiene estado inicial correcto', () => {
        const { form } = useMilestones();
        expect(form.value.title).toBe('');
        expect(form.value.completed).toBe(false);
    });

    it('handleStore en éxito redirige a milestones', async () => {
        vi.spyOn(milestonesService, 'store').mockResolvedValueOnce({
            status: true, message: 'OK', items: {} as any,
        });

        const { handleStore } = useMilestones();
        await handleStore();

        expect(mockPush).toHaveBeenCalledWith({ name: 'milestones', params: { projectId: 3 } });
    });

    it('handleUpdate en éxito muestra snackbar success', async () => {
        vi.spyOn(milestonesService, 'update').mockResolvedValueOnce({
            status: true, message: 'Actualizado', items: {} as any,
        });

        const { handleUpdate } = useMilestones();
        const appStore = useAppStore();
        await handleUpdate();

        expect(appStore.snackbar.text).toBe('Hito actualizado');
    });
});
