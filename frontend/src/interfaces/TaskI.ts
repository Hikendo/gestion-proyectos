import {
  ModelBaseI,
  DateTimeString,
} from "@/interfaces/ModelBaseI";

import {
  TaskPriority,
  TaskStatus,
} from "@/interfaces/enums";

import { BlockerI } from "@/interfaces/BlockerI";
import { ProjectI } from "@/interfaces/ProjectI";
import { ProjectPhaseI } from "@/interfaces/ProjectPhaseI";

import { TaskAttachmentI } from "@/interfaces/TaskAttachmentI";
import { TaskCommentI } from "@/interfaces/TaskCommentI";
import { TaskTimeLogI } from "@/interfaces/TaskTimeLogI";

import { UserI } from "@/interfaces/UserI";

export interface TaskI extends ModelBaseI {
  project_id: number; // Required en Laravel

  phase_id?: number | null; // Nullable en Laravel
  assigned_to?: number | null; // Nullable en Laravel
  created_by?: number | null; // Nullable en Laravel

  title: string; // Required en Laravel
  description?: string | null; // Nullable en Laravel

  priority?: TaskPriority | null; // Nullable enum Laravel
  status: TaskStatus; // Required enum Laravel

  due_date?: DateTimeString | null; // Nullable en Laravel

  estimated_hours?: number | null; // Nullable en Laravel
  worked_hours?: number | null; // Nullable en Laravel

  progress?: number | null; // Nullable en Laravel

  project?: ProjectI;
  phase?: ProjectPhaseI;

  assignee?: UserI;
  creator?: UserI;

  comments?: TaskCommentI[];
  attachments?: TaskAttachmentI[];

  timeLogs?: TaskTimeLogI[];

  blockers?: BlockerI[];
}

export interface TaskErroresFormI {
  project_id: string[]; // Required en Laravel

  phase_id?: string[]; // Nullable en Laravel
  assigned_to?: string[]; // Nullable en Laravel
  created_by?: string[]; // Nullable en Laravel

  title: string[]; // Required en Laravel
  description?: string[]; // Nullable en Laravel

  priority?: string[]; // Nullable enum Laravel
  status: string[]; // Required enum Laravel

  due_date?: string[]; // Nullable en Laravel

  estimated_hours?: string[]; // Nullable en Laravel
  worked_hours?: string[]; // Nullable en Laravel

  progress?: string[]; // Nullable en Laravel
}

export interface TaskAxiosErrorI {
  message: string;
  errors: TaskErroresFormI;
}
