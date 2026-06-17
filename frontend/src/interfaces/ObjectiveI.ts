import { ModelBaseI } from "@/interfaces/ModelBaseI";
import { ObjectiveType } from "@/interfaces/enums";
import { ProjectI } from "@/interfaces/ProjectI";

export interface ObjectiveI extends ModelBaseI {
  project_id: number; // Required en Laravel
  phase_id?: number | null; // Nullable en Laravel

  type: ObjectiveType; // Enum Laravel
  title: string; // Required en Laravel
  description?: string | null; // Nullable en Laravel

  completed: boolean; // Required en Laravel

  project?: ProjectI;
  phase?: { id: number; name: string } | null;
}

export interface ObjectiveErroresFormI {
  project_id: string[]; // Required en Laravel

  type: string[]; // Required en Laravel
  title: string[]; // Required en Laravel
  description?: string[]; // Nullable en Laravel

  completed: string[]; // Required en Laravel
}

export interface ObjectiveAxiosErrorI {
  message: string;
  errors: ObjectiveErroresFormI;
}
