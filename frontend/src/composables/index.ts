import {
    authService,
    dashboardService,
    projectBlockersService,
    projectDeliverablesService,
    projectMembersService,
    projectMilestonesService,
    projectObjectivesService,
    projectPhasesService,
    projectPlansService,
    projectRisksService,
    projectsService,
    projectTasksService,
    rolesService,
    taskCommentsService,
    taskTimeLogsService,
    ticketsService,
    usersService,
} from '../services';
import { createServiceComposable } from './createServiceComposable';
export { useServiceRequest } from './useServiceRequest';

const authFields = ['name', 'email', 'password', 'password_confirmation', 'role'] as const;
const userFields = ['name', 'email', 'password', 'password_confirmation', 'role'] as const;
const projectFields = ['name', 'code', 'description', 'status', 'start_date', 'end_date', 'budget', 'progress', 'owner_id'] as const;
const memberFields = ['user_id', 'role'] as const;
const phaseFields = ['name', 'start_date', 'end_date', 'progress'] as const;
const planFields = ['scope', 'requirements', 'technical_notes'] as const;
const objectiveFields = ['type', 'title', 'description', 'completed'] as const;
const milestoneFields = ['title', 'target_date', 'completed'] as const;
const deliverableFields = ['name', 'description', 'delivery_date', 'approved'] as const;
const riskFields = ['title', 'description', 'impact', 'probability', 'mitigation_plan'] as const;
const blockerFields = ['task_id', 'title', 'description', 'severity', 'resolved'] as const;
const taskFields = [
    'phase_id',
    'assigned_to',
    'created_by',
    'title',
    'description',
    'priority',
    'status',
    'due_date',
    'estimated_hours',
    'worked_hours',
    'progress',
] as const;
const ticketFields = ['created_by', 'assigned_to', 'subject', 'description', 'status', 'priority'] as const;
const commentFields = ['comment'] as const;
const timeLogFields = ['minutes', 'description'] as const;

export const useAuthService = createServiceComposable(authService, authFields);
export const useDashboardService = createServiceComposable(dashboardService);
export const useRolesService = createServiceComposable(rolesService);
export const useUsersService = createServiceComposable(usersService, userFields);
export const useProjectsService = createServiceComposable(projectsService, projectFields);
export const useProjectMembersService = createServiceComposable(projectMembersService, memberFields);
export const useProjectPhasesService = createServiceComposable(projectPhasesService, phaseFields);
export const useProjectPlansService = createServiceComposable(projectPlansService, planFields);
export const useProjectObjectivesService = createServiceComposable(projectObjectivesService, objectiveFields);
export const useProjectMilestonesService = createServiceComposable(projectMilestonesService, milestoneFields);
export const useProjectDeliverablesService = createServiceComposable(projectDeliverablesService, deliverableFields);
export const useProjectRisksService = createServiceComposable(projectRisksService, riskFields);
export const useProjectBlockersService = createServiceComposable(projectBlockersService, blockerFields);
export const useProjectTasksService = createServiceComposable(projectTasksService, taskFields);
export const useTicketsService = createServiceComposable(ticketsService, ticketFields);
export const useTaskCommentsService = createServiceComposable(taskCommentsService, commentFields);
export const useTaskTimeLogsService = createServiceComposable(taskTimeLogsService, timeLogFields);

export { useUsers } from './useUsers';
export { useUserForm } from './useUserForm';
export { useUserCreate } from './useUserCreate';
export { useUserUpdate } from './useUserUpdate';
export { useUserDelete } from './useUserDelete';
export { useUserList } from './useUserList';
export { useRoles as useRolesList } from './useRolesList';
export { useFieldLock, useField } from './useFieldLock';
