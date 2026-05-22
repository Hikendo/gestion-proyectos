import type { BaseModel } from './ModelBaseI';
import type { Task } from './task';
import type { User } from './user';

export interface TaskAttachment extends BaseModel {
    task_id: number;
    file_name: string;
    file_path: string;
    mime_type?: string | null;
    uploaded_by: number;
    task?: Task;
    uploader?: User;
}
