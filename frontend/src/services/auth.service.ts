import { AxiosError } from "axios";

import {
  LoginResponse,
  MeResponse,
  RegisterResponse,
  UserPayload,
} from "./types";

import {
  clearAuthToken,
  setAuthToken,
} from "./http";


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

/**
 * LOGIN
 */
export const login = async (payload: LoginPayload) => {
  try {
    const { data } = await apiWithToken.post<LoginResponse>(
      `/auth/login`,
      payload
    );

    setAuthToken(data.token);

    return {
      status: true,
      message: "Login exitoso",
      items: data,
    };
  } catch (error) {
    const err = error as AxiosError;

    return {
      status: false,
      message: "Error en el servidor",
    };
  }
};

/**
 * ME
 */
export const me = async () => {
  try {
    const { data } = await apiWithToken.get<MeResponse>(
      `/auth/me`
    );

    return {
      status: true,
      message: "OK",
      items: data,
    };
  } catch (error) {
    return {
      status: false,
      message: "Error en el servidor",
    };
  }
};

/**
 * LOGOUT
 */
export const logout = async () => {
  try {
    const { data } = await apiWithToken.post<{ message: string }>(
      `/auth/logout`
    );

    clearAuthToken();

    return {
      status: true,
      message: data.message,
    };
  } catch (error) {
    clearAuthToken();

    return {
      status: false,
      message: "Error en el servidor",
    };
  }
};

/**
 * REGISTER
 */
export const register = async (payload: RegisterPayload) => {
  try {
    const { data } = await apiWithToken.post<RegisterResponse>(
      `/auth/register`,
      payload
    );

    return {
      status: true,
      message: "Usuario registrado correctamente",
      items: data,
    };
  } catch (error) {
    const err = error as AxiosError;

    if (err?.response?.status === 422) {
      return {
        status: false,
        message: "Llena correctamente el formulario",
        errors: err.response.data,
      };
    }

    return {
      status: false,
      message: "Error en el servidor",
    };
  }
};
