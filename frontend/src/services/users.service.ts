import type {
    MessageResponse,
    PaginatedResponse,
    ResourceResponse,
    UserListQuery,
    UserMetricsResponse,
    UserPayload,
    UserResourceItem,
} from './types';
import { requestJson } from './http';

export const usersService = {
    list(query?: UserListQuery): Promise<PaginatedResponse<UserResourceItem>> {
        return requestJson<PaginatedResponse<UserResourceItem>>('/users', { query });
    },

    get(userId: number): Promise<ResourceResponse<UserResourceItem>> {
        return requestJson<ResourceResponse<UserResourceItem>>(`/users/${userId}`);
    },

    create(payload: UserPayload): Promise<ResourceResponse<UserResourceItem>> {
        return requestJson<ResourceResponse<UserResourceItem>>('/users', {
            method: 'POST',
            body: payload,
        });
    },

    update(userId: number, payload: UserPayload): Promise<ResourceResponse<UserResourceItem>> {
        return requestJson<ResourceResponse<UserResourceItem>>(`/users/${userId}`, {
            method: 'PUT',
            body: payload,
        });
    },

    remove(userId: number): Promise<MessageResponse> {
        return requestJson<MessageResponse>(`/users/${userId}`, {
            method: 'DELETE',
        });
    },

    metrics(userId: number): Promise<UserMetricsResponse> {
        return requestJson<UserMetricsResponse>(`/users/${userId}/metrics`);
    },
};
