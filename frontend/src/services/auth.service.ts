import type { LoginResponse, MeResponse, RegisterResponse, UserPayload } from './types';
import { clearAuthToken, requestJson, setAuthToken } from './http';

export interface LoginPayload {
    email: string;
    password: string;
}

export interface RegisterPayload extends UserPayload {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    role: string;
}

export const authService = {
    async login(payload: LoginPayload): Promise<LoginResponse> {
        const response = await requestJson<LoginResponse>('/auth/login', {
            method: 'POST',
            body: payload,
        });

        setAuthToken(response.token);

        return response;
    },

    me(): Promise<MeResponse> {
        return requestJson<MeResponse>('/auth/me');
    },

    async logout(): Promise<{ message: string }> {
        try {
            return await requestJson<{ message: string }>('/auth/logout', {
                method: 'POST',
            });
        } finally {
            clearAuthToken();
        }
    },

    register(payload: RegisterPayload): Promise<RegisterResponse> {
        return requestJson<RegisterResponse>('/auth/register', {
            method: 'POST',
            body: payload,
        });
    },
};
