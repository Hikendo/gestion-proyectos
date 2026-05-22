import type { BaseModel, DateTimeString } from './ModelBaseI';
import type { TaskPriority, TaskStatus } from './enums';
import type { Blocker } from './BlockerI';
import type { Project } from './project';
import type { ProjectPhase } from './project-phase';
import type { TaskAttachment } from './task-attachment';
import type { TaskComment } from './task-comment';
import type { TaskTimeLog } from './task-time-log';
import type { User } from './user';

export interface Task extends BaseModel {
    project_id: number;
    phase_id?: number | null;
    assigned_to?: number | null;
    created_by?: number | null;
    title: string;
    description?: string | null;
    priority?: TaskPriority | null;
    status: TaskStatus;
    due_date?: DateTimeString | null;
    estimated_hours?: number | null;
    worked_hours?: number | null;
    progress?: number | null;
    project?: Project;
    phase?: ProjectPhase;
    assignee?: User;
    creator?: User;
    comments?: TaskComment[];
    attachments?: TaskAttachment[];
    timeLogs?: TaskTimeLog[];
    blockers?: Blocker[];
}
