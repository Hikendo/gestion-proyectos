import { ModelBaseI } from "@/interfaces/ModelBaseI";
import { UserI } from "@/interfaces/UserI";

export interface ActivityLogI extends ModelBaseI {
  user_id: number; // Required en Laravel
  module: string; // Required en Laravel
  action: string; // Required en Laravel
  data: Record<string, unknown>; // JSON/Object requerido
  ip_address?: string | null; // Nullable en Laravel

  user?: UserI;
}

export interface ActivityLogErroresFormI {
  user_id: string[]; // Required en Laravel
  module: string[]; // Required en Laravel
  action: string[]; // Required en Laravel
  data: string[]; // Required en Laravel
  ip_address?: string[]; // Nullable en Laravel
}

export interface ActivityLogAxiosErrorI {
  message: string;
  errors: ActivityLogErroresFormI;
}
