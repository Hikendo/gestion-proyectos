import { AxiosError } from "axios";
import { apiWithToken } from "@/services/http";
import { ResponseBaseI } from "@/interfaces/ResponseBaseI";
import { PaginacionScoutI } from "@/interfaces/PaginacionScoutI";
import { UserI } from "@/interfaces/UserI";
import { UserAxiosErrorI } from "@/interfaces/UserI";
import { UserMetricI } from "@/interfaces/UserMetricI";
import type { UserListQuery, UserPayload } from "./types";

// ─── All (sin paginación) ─────────────────────────────────────────────────────

interface UsersAllResponseI extends ResponseBaseI {
  items: Pick<UserI, 'id' | 'name' | 'email'>[];
}

export const all = async () => {
  try {
    const { data } = await apiWithToken.get<UsersAllResponseI>("/users/all");
    return {
      status: true,
      message: data.message,
      items: data.items,
    };
  } catch (error) {
    return { status: false, message: "Error en el servidor", items: [] as Pick<UserI, 'id' | 'name' | 'email'>[] };
  }
};

// ─── Index ────────────────────────────────────────────────────────────────────

interface UsersItemsI extends PaginacionScoutI {
  data: UserI[];
}

interface UsersResponseI extends ResponseBaseI {
  items: UsersItemsI;
}

export const index = async (query?: UserListQuery) => {
  try {
    const { data } = await apiWithToken.get<UsersResponseI>("/users", { params: query });
    return {
      status: true,
      message: data.message,
      items: data.items,
    };
  } catch (error) {
    return { status: false, message: "Error en el servidor" };
  }
};

// ─── Show ─────────────────────────────────────────────────────────────────────

interface UserResponseI extends ResponseBaseI {
  items: UserI;
}

export const show = async (userId: number) => {
  try {
    const { data } = await apiWithToken.get<UserResponseI>(`/users/${userId}`);
    return {
      status: true,
      message: data.message,
      items: data.items,
    };
  } catch (error) {
    return { status: false, message: "Error en el servidor" };
  }
};

// ─── Store ────────────────────────────────────────────────────────────────────

export const store = async (payload: UserPayload) => {
  try {
    const { data } = await apiWithToken.post<UserResponseI>("/users", payload);
    return {
      status: true,
      message: data.message,
      items: data.items,
    };
  } catch (error) {
    const err = error as AxiosError;
    if (err?.response?.status === 422) {
      const errorsForm = err.response.data as UserAxiosErrorI;
      return {
        status: false,
        message: "Llena correctamente el formulario",
        errors: errorsForm.errors,
      };
    }
    return { status: false, message: "Error en el servidor" };
  }
};

// ─── Update ───────────────────────────────────────────────────────────────────

export const update = async (userId: number, payload: UserPayload) => {
  try {
    const { data } = await apiWithToken.put<UserResponseI>(`/users/${userId}`, payload);
    return {
      status: true,
      message: data.message,
      items: data.items,
    };
  } catch (error) {
    const err = error as AxiosError;
    if (err?.response?.status === 422) {
      const errorsForm = err.response.data as UserAxiosErrorI;
      return {
        status: false,
        message: "Llena correctamente el formulario",
        errors: errorsForm.errors,
      };
    }
    return { status: false, message: "Error en el servidor" };
  }
};

// ─── Destroy ──────────────────────────────────────────────────────────────────

export const destroy = async (userId: number) => {
  try {
    const { data } = await apiWithToken.delete<ResponseBaseI>(`/users/${userId}`);
    return {
      status: true,
      message: data.message,
    };
  } catch (error) {
    return { status: false, message: "Error en el servidor" };
  }
};

// ─── Metrics ──────────────────────────────────────────────────────────────────

interface UserMetricResponseI extends ResponseBaseI {
  items: UserMetricI;
}

export const metrics = async (userId: number) => {
  try {
    const { data } = await apiWithToken.get<UserMetricResponseI>(`/users/${userId}/metrics`);
    return {
      status: true,
      message: data.message,
      items: data.items,
    };
  } catch (error) {
    return { status: false, message: "Error en el servidor" };
  }
};
