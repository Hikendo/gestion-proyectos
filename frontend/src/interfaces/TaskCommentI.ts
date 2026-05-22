import { ModelBaseI } from "@/interfaces/ModelBaseI";

import { TaskI } from "@/interfaces/TaskI";
import { UserI } from "@/interfaces/UserI";

export interface TaskCommentI extends ModelBaseI {
  task_id: number; // Required en Laravel
  user_id: number; // Required en Laravel

  comment: string; // Required en Laravel

  task?: TaskI;
  user?: UserI;
}

export interface TaskCommentErroresFormI {
  task_id: string[]; // Required en Laravel
  user_id: string[]; // Required en Laravel

  comment: string[]; // Required en Laravel
}

export interface TaskCommentAxiosErrorI {
  message: string;
  errors: TaskCommentErroresFormI;
}
