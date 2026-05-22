import type {
    CollectionResponse,
    MessageResponse,
    ProjectMemberPayload,
    ProjectMemberItem,
    ResourceResponse,
} from './types';
import { requestJson } from './http';

export const projectMembersService = {
    list(projectId: number): Promise<CollectionResponse<ProjectMemberItem>> {
        return requestJson<CollectionResponse<ProjectMemberItem>>(`/projects/${projectId}/members`);
    },

    add(projectId: number, payload: ProjectMemberPayload): Promise<ResourceResponse<ProjectMemberItem>> {
        return requestJson<ResourceResponse<ProjectMemberItem>>(`/projects/${projectId}/members`, {
            method: 'POST',
            body: payload,
        });
    },

    remove(projectId: number, userId: number): Promise<MessageResponse> {
        return requestJson<MessageResponse>(`/projects/${projectId}/members/${userId}`, {
            method: 'DELETE',
        });
    },
};
