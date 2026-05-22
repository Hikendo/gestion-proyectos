import type { BaseModel } from './ModelBaseI';
import type { Task } from './task';
import type { User } from './user';

export interface TaskComment extends BaseModel {
    task_id: number;
    user_id: number;
    comment: string;
    task?: Task;
    user?: User;
}
