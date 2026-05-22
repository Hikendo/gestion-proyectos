import { ModelBaseI } from "@/interfaces/ModelBaseI";
import {
  RiskImpact,
  RiskProbability,
} from "@/interfaces/enums";

import { ProjectI } from "@/interfaces/ProjectI";

export interface RiskI extends ModelBaseI {
  project_id: number; // Required en Laravel

  title: string; // Required en Laravel
  description?: string | null; // Nullable en Laravel

  impact: RiskImpact; // Enum Laravel
  probability: RiskProbability; // Enum Laravel

  mitigation_plan?: string | null; // Nullable en Laravel

  project?: ProjectI;
}

export interface RiskErroresFormI {
  project_id: string[]; // Required en Laravel

  title: string[]; // Required en Laravel
  description?: string[]; // Nullable en Laravel

  impact: string[]; // Required en Laravel
  probability: string[]; // Required en Laravel

  mitigation_plan?: string[]; // Nullable en Laravel
}

export interface RiskAxiosErrorI {
  message: string;
  errors: RiskErroresFormI;
}
