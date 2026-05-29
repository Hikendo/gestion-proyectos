import { AxiosError } from "axios";
import { apiWithToken } from "@/services/http";
import { ResponseBaseI } from "@/interfaces/ResponseBaseI";
import { BlockerI } from "@/interfaces/BlockerI";
import type { BlockerListQuery, BlockerPayload } from "./types";

// ─── Index ────────────────────────────────────────────────────────────────────

interface BlockersResponseI extends ResponseBaseI {
  items: BlockerI[];
}

export const index = async (projectId: number, query?: BlockerListQuery) => {
  try {
    const { data } = await apiWithToken.get<BlockersResponseI>(
      `/projects/${projectId}/blockers`,
      { params: query }
    );
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

interface BlockerResponseI extends ResponseBaseI {
  items: BlockerI;
}

export const store = async (projectId: number, payload: BlockerPayload) => {
  try {
    const { data } = await apiWithToken.post<BlockerResponseI>(`/projects/${projectId}/blockers`, payload);
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
        errors: (err.response.data as any)?.errors,
      };
    }
    return { status: false, message: "Error en el servidor" };
  }
};

// ─── Update ───────────────────────────────────────────────────────────────────

export const update = async (projectId: number, blockerId: number, payload: BlockerPayload) => {
  try {
    const { data } = await apiWithToken.put<BlockerResponseI>(`/projects/${projectId}/blockers/${blockerId}`, payload);
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
        errors: (err.response.data as any)?.errors,
      };
    }
    return { status: false, message: "Error en el servidor" };
  }
};

// ─── Resolve ──────────────────────────────────────────────────────────────────

export const resolve = async (projectId: number, blockerId: number) => {
  try {
    const { data } = await apiWithToken.patch<BlockerResponseI>(
      `/projects/${projectId}/blockers/${blockerId}/resolve`
    );
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
        message: (err.response.data as any)?.message || "No se puede resolver este blocker",
      };
    }
    return { status: false, message: "Error en el servidor" };
  }


};
  export const show = async (projectId: number, blockerId: number) => {
    try {
      const { data } = await apiWithToken.get<BlockerResponseI>(`/projects/${projectId}/blockers/${blockerId}`);
      return {
        status: true,
        message: data.message,
        items: data.items,
      };
    } catch (error) {
      return { status: false, message: "Error en el servidor" };
    }
  };
