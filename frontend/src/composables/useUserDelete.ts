import { ref } from 'vue';
import { useUsersService } from './index';

export function useUserDelete() {
    const usersService = useUsersService();
    const isLoading = ref(false);
    const successMessage = ref('');

    async function handleDelete(userId: number): Promise<boolean> {
        successMessage.value = '';
        isLoading.value = true;

        const response = await usersService.call('destroy', userId);

        isLoading.value = false;

        if (response) {
            successMessage.value = `Usuario ${userId} eliminado correctamente.`;
            return true;
        }

        return false;
    }

    return {
        isLoading,
        successMessage,
        usersService,
        handleDelete,
    };
}
