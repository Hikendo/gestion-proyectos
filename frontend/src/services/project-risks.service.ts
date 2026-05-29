import { AxiosError } from "axios";
import { apiWithToken } from "@/services/http";
import { ResponseBaseI } from "@/interfaces/ResponseBaseI";
import { RiskI } from "@/interfaces/RiskI";
import type { RiskPayload } from "./types";

// ─── Index ────────────────────────────────────────────────────────────────────

interface RisksResponseI extends ResponseBaseI {
  items: RiskI[];
}

export const index = async (projectId: number) => {
  try {
    const { data } = await apiWithToken.get<RisksResponseI>(`/projects/${projectId}/risks`);
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

interface RiskResponseI extends ResponseBaseI {
  items: RiskI;
}

export const store = async (projectId: number, payload: RiskPayload) => {
  try {
    const { data } = await apiWithToken.post<RiskResponseI>(`/projects/${projectId}/risks`, payload);
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

export const update = async (projectId: number, riskId: number, payload: RiskPayload) => {
  try {
    const { data } = await apiWithToken.put<RiskResponseI>(`/projects/${projectId}/risks/${riskId}`, payload);
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

// ─── Destroy ──────────────────────────────────────────────────────────────────

export const destroy = async (projectId: number, riskId: number) => {
  try {
    const { data } = await apiWithToken.delete<ResponseBaseI>(`/projects/${projectId}/risks/${riskId}`);
    return {
      status: true,
      message: data.message,
    };
  } catch (error) {
    return { status: false, message: "Error en el servidor" };
  }

};
export const show = async (projectId: number, riskId: number) => {
  try {
    const { data } = await apiWithToken.get<RiskResponseI>(`/projects/${projectId}/risks/${riskId}`);
    return {
      status: true,
      message: data.message,
      items: data.items,
    };
  } catch (error) {
    return { status: false, message: "Error en el servidor" };
  }
};
