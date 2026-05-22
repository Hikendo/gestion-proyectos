import type {
    CollectionResponse,
    MessageResponse,
    ObjectiveItem,
    ObjectivePayload,
    ResourceResponse,
} from './types';
import { requestJson } from './http';

export const projectObjectivesService = {
    list(projectId: number): Promise<CollectionResponse<ObjectiveItem>> {
        return requestJson<CollectionResponse<ObjectiveItem>>(`/projects/${projectId}/objectives`);
    },

    create(projectId: number, payload: ObjectivePayload): Promise<ResourceResponse<ObjectiveItem>> {
        return requestJson<ResourceResponse<ObjectiveItem>>(`/projects/${projectId}/objectives`, {
            method: 'POST',
            body: payload,
        });
    },

    update(projectId: number, objectiveId: number, payload: ObjectivePayload): Promise<ResourceResponse<ObjectiveItem>> {
        return requestJson<ResourceResponse<ObjectiveItem>>(`/projects/${projectId}/objectives/${objectiveId}`, {
            method: 'PUT',
            body: payload,
        });
    },
};
