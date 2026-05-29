import { AxiosError } from "axios";
import { apiWithToken } from "@/services/http";
import { ResponseBaseI } from "@/interfaces/ResponseBaseI";
import { DeliverableI } from "@/interfaces/DeliverableI";
import type { DeliverablePayload } from "./types";

// ─── Index ────────────────────────────────────────────────────────────────────

interface DeliverablesResponseI extends ResponseBaseI {
  items: DeliverableI[];
}

export const index = async (projectId: number) => {
  try {
    const { data } = await apiWithToken.get<DeliverablesResponseI>(`/projects/${projectId}/deliverables`);
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

interface DeliverableResponseI extends ResponseBaseI {
  items: DeliverableI;
}

export const store = async (projectId: number, payload: DeliverablePayload) => {
  try {
    const { data } = await apiWithToken.post<DeliverableResponseI>(`/projects/${projectId}/deliverables`, payload);
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
export const show = async (projectId: number, deliverableId: number) => {
  try {
    const { data } = await apiWithToken.get<DeliverableResponseI>(`/projects/${projectId}/deliverables/${deliverableId}`);
    return {
      status: true,
      message: data.message,
      items: data.items,
    };
  } catch (error) {
    return { status: false, message: "Error en el servidor" };
  }
}
// ─── Update ───────────────────────────────────────────────────────────────────

export const update = async (projectId: number, deliverableId: number, payload: DeliverablePayload) => {
  try {
    const { data } = await apiWithToken.put<DeliverableResponseI>(`/projects/${projectId}/deliverables/${deliverableId}`, payload);
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

// ─── Approve ──────────────────────────────────────────────────────────────────

export const approve = async (projectId: number, deliverableId: number) => {
  try {
    const { data } = await apiWithToken.patch<DeliverableResponseI>(
      `/projects/${projectId}/deliverables/${deliverableId}/approve`
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
        message: (err.response.data as any)?.message || "No se puede aprobar este entregable",
      };
    }
    return { status: false, message: "Error en el servidor" };
  }

};
