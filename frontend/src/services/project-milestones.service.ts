import type {
    CollectionResponse,
    DeliverableItem,
    MessageResponse,
    MilestoneItem,
    MilestonePayload,
    ResourceResponse,
} from './types';
import { requestJson } from './http';

export const projectMilestonesService = {
    list(projectId: number): Promise<CollectionResponse<MilestoneItem>> {
        return requestJson<CollectionResponse<MilestoneItem>>(`/projects/${projectId}/milestones`);
    },

    create(projectId: number, payload: MilestonePayload): Promise<ResourceResponse<MilestoneItem>> {
        return requestJson<ResourceResponse<MilestoneItem>>(`/projects/${projectId}/milestones`, {
            method: 'POST',
            body: payload,
        });
    },

    update(projectId: number, milestoneId: number, payload: MilestonePayload): Promise<ResourceResponse<MilestoneItem>> {
        return requestJson<ResourceResponse<MilestoneItem>>(`/projects/${projectId}/milestones/${milestoneId}`, {
            method: 'PUT',
            body: payload,
        });
    },

    remove(projectId: number, milestoneId: number): Promise<MessageResponse> {
        return requestJson<MessageResponse>(`/projects/${projectId}/milestones/${milestoneId}`, {
            method: 'DELETE',
        });
    },
};
