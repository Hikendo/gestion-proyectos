import { ModelBaseI } from "@/interfaces/ModelBaseI";

import { TaskI } from "@/interfaces/TaskI";
import { UserI } from "@/interfaces/UserI";

export interface TaskTimeLogI extends ModelBaseI {
  task_id: number; // Required en Laravel
  user_id: number; // Required en Laravel

  minutes: number; // Required en Laravel

  description?: string | null; // Nullable en Laravel

  task?: TaskI;
  user?: UserI;
}

export interface TaskTimeLogErroresFormI {
  task_id: string[]; // Required en Laravel
  user_id: string[]; // Required en Laravel

  minutes: string[]; // Required en Laravel

  description?: string[]; // Nullable en Laravel
}

export interface TaskTimeLogAxiosErrorI {
  message: string;
  errors: TaskTimeLogErroresFormI;
}
