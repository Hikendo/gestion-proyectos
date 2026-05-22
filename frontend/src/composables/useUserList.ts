import { ref, reactive } from 'vue';
import { useUsersService } from './index';
import type { User } from '../interfaces/user';

export interface UserListState {
    page: number;
    search: string;
    total: number;
}

export function useUserList() {
    const usersService = useUsersService();
    const isLoading = ref(false);
    const users = ref<User[]>([]);
    const listState = reactive<UserListState>({
        page: 1,
        search: '',
        total: 0,
    });

    async function loadUsers(): Promise<boolean> {
        isLoading.value = true;

        const response = await usersService.call('list');

        isLoading.value = false;

        if (response) {
            users.value = Array.isArray(response) ? response : [];
            return true;
        }

        return false;
    }

    function searchUsers(query: string): void {
        listState.search = query;
        listState.page = 1;
    }

    function goToPage(page: number): void {
        listState.page = page;
    }

    return {
        users,
        isLoading,
        listState,
        usersService,
        loadUsers,
        searchUsers,
        goToPage,
    };
}
