import type {
    BlockerI,
    DeliverableI,
    MilestoneI,
    ObjectiveI,
    ProjectI,
    ProjectMemberI,
    ProjectPhaseI,
    ProjectPlanI,
    RiskI,
    TaskI,
    TaskAttachmentI,
    TaskCommentI,
    TaskTimeLogI,
    TicketI,
    UserI,
    UserMetricI,
    ProjectStatus,
    TaskPriority,
    TaskStatus,
    TicketPriority,
    TicketStatus,
    ProjectMemberRole,
} from '../interfaces';

// ─── Dashboard ────────────────────────────────────────────────────────────────

export interface DashboardSummary {
    total_projects: number;
    my_pending_tasks: number;
    open_tickets: number;
    active_blockers_count?: number;
    active_risks_count?: number;
}

export interface DashboardProjectItem extends Pick<ProjectI, 'id' | 'name' | 'code' | 'status' | 'progress' | 'end_date'> {
    tasks_count?: number;
    tickets_count?: number;
}

export interface DashboardTaskItem extends Pick<TaskI, 'id' | 'title' | 'status' | 'priority' | 'due_date' | 'project_id'> {
    project?: Pick<ProjectI, 'id' | 'name' | 'code'>;
}

export interface DashboardTicketItem extends Pick<TicketI, 'id' | 'subject' | 'priority' | 'project_id'> {
    created_at?: string;
    project?: Pick<ProjectI, 'id' | 'name' | 'code'>;
}

export interface DashboardBlockerItem extends Pick<BlockerI, 'id' | 'title' | 'severity' | 'project_id'> {
    task_id?: number | null;
    project?: Pick<ProjectI, 'id' | 'name' | 'code'>;
    task?: Pick<TaskI, 'id' | 'title'>;
}

export interface DashboardRiskItem extends Pick<RiskI, 'id' | 'title' | 'impact' | 'probability' | 'project_id'> {
    project?: Pick<ProjectI, 'id' | 'name' | 'code'>;
}

export interface DashboardPhaseItem extends Pick<ProjectPhaseI, 'id' | 'name' | 'progress' | 'end_date' | 'project_id'> {
    project?: Pick<ProjectI, 'id' | 'name' | 'code'>;
}

export interface DashboardObjectiveItem extends Pick<ObjectiveI, 'id' | 'title' | 'type' | 'completed' | 'project_id'> {
    project?: Pick<ProjectI, 'id' | 'name' | 'code'>;
}

export interface DashboardManagerTicketItem extends Pick<TicketI, 'id' | 'subject' | 'status' | 'priority' | 'project_id'> {
    created_at?: string;
    project?: Pick<ProjectI, 'id' | 'name' | 'code'>;
    creator?: Pick<UserI, 'id' | 'name' | 'email'>;
    assignee?: Pick<UserI, 'id' | 'name' | 'email'>;
}

export interface DashboardManagerTaskItem extends Pick<TaskI, 'id' | 'title' | 'status' | 'priority' | 'due_date' | 'project_id'> {
    project?: Pick<ProjectI, 'id' | 'name' | 'code'>;
    assignee?: Pick<UserI, 'id' | 'name' | 'email'>;
}

export interface DashboardClientTicketItem extends Pick<TicketI, 'id' | 'subject' | 'status' | 'priority' | 'project_id'> {
    created_at?: string;
    project?: Pick<ProjectI, 'id' | 'name' | 'code'>;
    assignee?: Pick<UserI, 'id' | 'name' | 'email'>;
}

export interface DashboardResponse {
    summary: DashboardSummary;
    // Flags de capacidad basados en permisos
    is_manager?: boolean;
    is_client?: boolean;
    can_view_tasks?: boolean;
    can_view_blockers?: boolean;
    can_view_risks?: boolean;
    can_view_phases?: boolean;
    can_view_objectives?: boolean;
    // Datos
    projects: DashboardProjectItem[];
    my_tasks: DashboardTaskItem[];
    open_tickets: DashboardTicketItem[];
    active_blockers?: DashboardBlockerItem[];
    active_risks?: DashboardRiskItem[];
    active_phases?: DashboardPhaseItem[];
    active_objectives?: DashboardObjectiveItem[];
    my_tickets?: DashboardClientTicketItem[];
    manager_tickets?: DashboardManagerTicketItem[];
    manager_tasks?: DashboardManagerTaskItem[];
}

// ─── Query params ─────────────────────────────────────────────────────────────

export interface PaginationQuery {
    page?: number;
    query?: string;
}

export interface UserListQuery extends PaginationQuery {
    role?: string;
}

export interface TaskListQuery extends PaginationQuery {
    status?: TaskStatus;
    assigned_to?: number;
    priority?: TaskPriority;
}

export interface TicketListQuery extends PaginationQuery {
    status?: TicketStatus;
    priority?: TicketPriority;
}

export interface BlockerListQuery {
    include_resolved?: boolean;
}

export interface ProjectListQuery extends PaginationQuery {
    status?: ProjectStatus;
}

// ─── Payload types ────────────────────────────────────────────────────────────

export interface ProjectPayload extends Partial<ProjectI> {}

export interface UserPayload extends Partial<UserI> {
    password?: string;
    password_confirmation?: string;
    role?: string;
}

export interface TaskPayload extends Partial<TaskI> {}

export interface TicketPayload extends Partial<TicketI> {}

export interface ProjectMemberPayload {
    user_id: number;
    role: ProjectMemberRole;
}

export interface ProjectPhasePayload extends Partial<ProjectPhaseI> {}

export interface ProjectPlanPayload extends Partial<ProjectPlanI> {}

export interface ObjectivePayload extends Partial<ObjectiveI> {}

export interface MilestonePayload extends Partial<MilestoneI> {}

export interface DeliverablePayload extends Partial<DeliverableI> {}

export interface RiskPayload extends Partial<RiskI> {}

export interface BlockerPayload extends Partial<BlockerI> {}

export interface TaskCommentPayload {
    comment: string;
}

export interface TaskTimeLogPayload {
    minutes: number;
    description?: string;
}
