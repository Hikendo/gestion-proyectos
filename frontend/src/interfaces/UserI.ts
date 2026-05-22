import {
  ModelBaseI,
  DateTimeString,
} from "@/interfaces/ModelBaseI";

import { UserMetricI } from "@/interfaces/UserMetricI";

export interface UserI extends ModelBaseI {
  name: string; // Required en Laravel
  email: string; // Required en Laravel

  email_verified_at?: DateTimeString | null; // Nullable en Laravel
  remember_token?: string | null; // Nullable en Laravel

  roles?: string[];
  permissions?: string[];

  metrics?: UserMetricI | null;
}

export interface UserErroresFormI {
  name?: string[]; // Required en Laravel
  email?: string[]; // Required en Laravel

  password?: string[]; // Required en Laravel en create
  password_confirmation?: string[]; // Required en Laravel en create

  role?: string[]; // Required dependiendo lógica Laravel
}

export interface UserAxiosErrorI {
  message: string;
  errors: UserErroresFormI;
}
