import { BlockerSeverity } from "@/interfaces/enums";
import { ModelBaseI } from "@/interfaces/ModelBaseI";
import { ProjectI } from "@/interfaces/ProjectI";
import { TaskI } from "@/interfaces/TaskI";


export interface BlockerI extends ModelBaseI {
  project_id: number; // Required en Laravel
  task_id?: number | null; // Nullable en Laravel

  title: string; // Required en Laravel
  description?: string | null; // Nullable en Laravel

  severity: BlockerSeverity; // Enum Laravel
  resolved: boolean; // Required en Laravel

  project?: ProjectI;
  task?: TaskI;
}

export interface BlockerErroresFormI {
  project_id: string[]; // Required en Laravel
  task_id?: string[]; // Nullable en Laravel

  title: string[]; // Required en Laravel
  description?: string[]; // Nullable en Laravel

  severity: string[]; // Required en Laravel
  resolved: string[]; // Required en Laravel
}

export interface BlockerAxiosErrorI {
  message: string;
  errors: BlockerErroresFormI;
}
