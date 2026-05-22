import { ModelBaseI } from "@/interfaces/ModelBaseI";

import { TaskI } from "@/interfaces/TaskI";
import { UserI } from "@/interfaces/UserI";

export interface TaskAttachmentI extends ModelBaseI {
  task_id: number; // Required en Laravel

  file_name: string; // Required en Laravel
  file_path: string; // Required en Laravel

  mime_type?: string | null; // Nullable en Laravel

  uploaded_by: number; // Required en Laravel

  task?: TaskI;
  uploader?: UserI;
}

export interface TaskAttachmentErroresFormI {
  task_id: string[]; // Required en Laravel

  file_name: string[]; // Required en Laravel
  file_path: string[]; // Required en Laravel

  mime_type?: string[]; // Nullable en Laravel

  uploaded_by: string[]; // Required en Laravel
}

export interface TaskAttachmentAxiosErrorI {
  message: string;
  errors: TaskAttachmentErroresFormI;
}
