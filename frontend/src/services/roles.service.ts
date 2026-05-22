import type { RoleItem } from './types';
import { requestJson } from './http';

export const rolesService = {
    list(): Promise<RoleItem[]> {
        return requestJson<RoleItem[]>('/roles');
    },
};
