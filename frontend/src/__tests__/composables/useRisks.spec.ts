import { describe, it, expect, vi, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useRisks } from '@/composables/useRisks';
import { useAppStore } from '@/store/useAppStore';
import * as risksService from '@/services/project-risks.service';

const mockPush = vi.fn();

vi.mock('vue-router', () => ({
    useRouter: () => ({ push: mockPush }),
    useRoute: () => ({ params: { projectId: '5' } }),
}));

describe('useRisks', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('form tiene estado inicial correcto', () => {
        const { form } = useRisks();
        expect(form.value.title).toBe('');
        expect(form.value.impact).toBe('medium');
        expect(form.value.probability).toBe('medium');
    });

    it('handleStore en éxito redirige a risks', async () => {
        vi.spyOn(risksService, 'store').mockResolvedValueOnce({
            status: true, message: 'OK', items: {} as any,
        });

        const { handleStore } = useRisks();
        await handleStore();

        expect(mockPush).toHaveBeenCalledWith({ name: 'risks', params: { projectId: 5 } });
    });

    it('handleUpdate en éxito muestra snackbar success', async () => {
        vi.spyOn(risksService, 'update').mockResolvedValueOnce({
            status: true, message: 'Actualizado', items: {} as any,
        });

        const { handleUpdate } = useRisks();
        const appStore = useAppStore();
        await handleUpdate();

        expect(appStore.snackbar.text).toBe('Riesgo actualizado');
    });
});
