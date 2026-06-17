import { DateString, ModelBaseI } from "@/interfaces/ModelBaseI";
import { ProjectI } from "@/interfaces/ProjectI";
export interface DeliverableI extends ModelBaseI {
  project_id: number; // Required en Laravel
  phase_id?: number | null; // Nullable en Laravel

  name: string; // Required en Laravel
  description?: string | null; // Nullable en Laravel
  delivery_date?: DateString | null; // Nullable en Laravel

  approved: boolean; // Required en Laravel

  project?: ProjectI;
  phase?: { id: number; name: string } | null;
}

export interface DeliverableErroresFormI {
  project_id: string[]; // Required en Laravel

  name: string[]; // Required en Laravel
  description?: string[]; // Nullable en Laravel
  delivery_date?: string[]; // Nullable en Laravel

  approved: string[]; // Required en Laravel
}

export interface DeliverableAxiosErrorI {
  message: string;
  errors: DeliverableErroresFormI;
}
