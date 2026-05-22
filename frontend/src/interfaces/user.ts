import type { BaseModel, DateTimeString } from './ModelBaseI';
import type { UserMetric } from './user-metric';

export interface User extends BaseModel {
    name: string;
    email: string;
    email_verified_at?: DateTimeString | null;
    remember_token?: string | null;
    roles?: string[];
    permissions?: string[];
    metrics?: UserMetric | null;
}

export interface UserValidationErrors {
    name?: string[];
    email?: string[];
    password?: string[];
    password_confirmation?: string[];
    role?: string[];
}

export interface UserAxiosError {
    message: string;
    errors: UserValidationErrors;
}
