import { AxiosError } from "axios";
import { apiWithToken, clearAuthToken, setAuthToken } from "@/services/http";
import { ResponseBaseI } from "@/interfaces/ResponseBaseI";
import { UserI } from "@/interfaces/UserI";

// ─── Login ───────────────────────────────────────────────────────────────────

interface LoginItemsI {
  token: string;
  user: UserI;
}

interface LoginResponseI extends ResponseBaseI {
  items: LoginItemsI;
}

export interface LoginPayload {
  email: string;
  password: string;
}

export const login = async (payload: LoginPayload) => {
  try {
    const { data } = await apiWithToken.post<LoginResponseI>("/auth/login", payload);
    setAuthToken(data.items.token);
    return {
      status: true,
      message: data.message,
      items: data.items,
    };
  } catch (error) {
    const err = error as AxiosError;
    if (err?.response?.status === 422) {
      return {
        status: false,
        message: "Credenciales incorrectas",
        errors: err.response.data,
      };
    }
    return { status: false, message: "Error en el servidor" };
  }
};

// ─── Me ──────────────────────────────────────────────────────────────────────

interface MeResponseI extends ResponseBaseI {
  items: UserI;
}

export const me = async () => {
  try {
    const { data } = await apiWithToken.get<MeResponseI>("/auth/me");
    return {
      status: true,
      message: data.message,
      items: data.items,
    };
  } catch (error) {
    return { status: false, message: "Error en el servidor" };
  }
};

// ─── Logout ───────────────────────────────────────────────────────────────────

export const logout = async () => {
  try {
    const { data } = await apiWithToken.post<ResponseBaseI>("/auth/logout");
    clearAuthToken();
    return {
      status: true,
      message: data.message,
    };
  } catch (error) {
    clearAuthToken();
    return { status: false, message: "Error en el servidor" };
  }
};

// ─── Register ─────────────────────────────────────────────────────────────────

interface RegisterItemsI extends Pick<UserI, "id" | "name" | "email"> {}

interface RegisterResponseI extends ResponseBaseI {
  items: RegisterItemsI;
}

export interface RegisterPayload {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export const register = async (payload: RegisterPayload) => {
  try {
    const { data } = await apiWithToken.post<RegisterResponseI>("/auth/register", payload);
    return {
      status: true,
      message: data.message,
      items: data.items,
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
    return { status: false, message: "Error en el servidor" };
  }
};
