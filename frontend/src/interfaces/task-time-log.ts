import type { BaseModel } from './ModelBaseI';
import type { Task } from './task';
import type { User } from './user';

export interface TaskTimeLog extends BaseModel {
    task_id: number;
    user_id: number;
    minutes: number;
    description?: string | null;
    task?: Task;
    user?: User;
}
