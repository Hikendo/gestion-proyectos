import type {
    BlockerItem,
    BlockerListQuery,
    BlockerPayload,
    BlockerResolutionResponse,
    CollectionResponse,
    MessageResponse,
    ResourceResponse,
} from './types';
import { requestJson } from './http';

export const projectBlockersService = {
    list(projectId: number, query?: BlockerListQuery): Promise<CollectionResponse<BlockerItem>> {
        return requestJson<CollectionResponse<BlockerItem>>(`/projects/${projectId}/blockers`, { query });
    },

    create(projectId: number, payload: BlockerPayload): Promise<ResourceResponse<BlockerItem>> {
        return requestJson<ResourceResponse<BlockerItem>>(`/projects/${projectId}/blockers`, {
            method: 'POST',
            body: payload,
        });
    },

    update(projectId: number, blockerId: number, payload: BlockerPayload): Promise<ResourceResponse<BlockerItem>> {
        return requestJson<ResourceResponse<BlockerItem>>(`/projects/${projectId}/blockers/${blockerId}`, {
            method: 'PUT',
            body: payload,
        });
    },

    resolve(projectId: number, blockerId: number): Promise<BlockerResolutionResponse> {
        return requestJson<BlockerResolutionResponse>(`/projects/${projectId}/blockers/${blockerId}/resolve`, {
            method: 'PATCH',
        });
    },
};
