import { AxiosError } from "axios";
import { apiWithToken } from "@/services/http";
import { ResponseBaseI } from "@/interfaces/ResponseBaseI";
import { ObjectiveI } from "@/interfaces/ObjectiveI";
import type { ObjectivePayload } from "./types";

// ─── Index ────────────────────────────────────────────────────────────────────

interface ObjectivesResponseI extends ResponseBaseI {
  items: ObjectiveI[];
}

export const index = async (projectId: number) => {
  try {
    const { data } = await apiWithToken.get<ObjectivesResponseI>(`/projects/${projectId}/objectives`);
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

interface ObjectiveResponseI extends ResponseBaseI {
  items: ObjectiveI;
}

export const store = async (projectId: number, payload: ObjectivePayload) => {
  try {
    const { data } = await apiWithToken.post<ObjectiveResponseI>(`/projects/${projectId}/objectives`, payload);
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

export const update = async (projectId: number, objectiveId: number, payload: ObjectivePayload) => {
  try {
    const { data } = await apiWithToken.put<ObjectiveResponseI>(`/projects/${projectId}/objectives/${objectiveId}`, payload);
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
export const show = async (projectId: number, objectiveId: number) => {
  try {
    const { data } = await apiWithToken.get<ObjectiveResponseI>(`/projects/${projectId}/objectives/${objectiveId}`);
    return {
      status: true,
      message: data.message,
      items: data.items,
    };
  } catch (error) {
    return { status: false, message: "Error en el servidor" };
  }
};
