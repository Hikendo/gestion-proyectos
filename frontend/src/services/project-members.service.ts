import { AxiosError } from "axios";
import { apiWithToken } from "@/services/http";
import { ResponseBaseI } from "@/interfaces/ResponseBaseI";
import { ProjectMemberI } from "@/interfaces/ProjectMemberI";
import { ProjectMemberAxiosErrorI } from "@/interfaces/ProjectMemberI";
import type { ProjectMemberPayload } from "./types";

// ─── Index ────────────────────────────────────────────────────────────────────

interface MembersResponseI extends ResponseBaseI {
  items: ProjectMemberI[];
}

export const index = async (projectId: number) => {
  try {
    const { data } = await apiWithToken.get<MembersResponseI>(`/projects/${projectId}/members`);
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

interface MemberResponseI extends ResponseBaseI {
  items: ProjectMemberI;
}

export const store = async (projectId: number, payload: ProjectMemberPayload) => {
  try {
    const { data } = await apiWithToken.post<MemberResponseI>(`/projects/${projectId}/members`, payload);
    return {
      status: true,
      message: data.message,
      items: data.items,
    };
  } catch (error) {
    const err = error as AxiosError;
    if (err?.response?.status === 422 || err?.response?.status === 409) {
      const errorsForm = err.response.data as ProjectMemberAxiosErrorI;
      return {
        status: false,
        message: errorsForm.message || "Llena correctamente el formulario",
        errors: errorsForm.errors,
      };
    }
    return { status: false, message: "Error en el servidor" };
  }
};

// ─── Update (cambiar rol de miembro) ──────────────────────────────────────────

export const update = async (projectId: number, userId: number, role: string) => {
  try {
    const { data } = await apiWithToken.put<MemberResponseI>(`/projects/${projectId}/members/${userId}`, { role });
    return {
      status: true,
      message: data.message,
      items: data.items,
    };
  } catch (error) {
    return { status: false, message: "Error en el servidor" };
  }
};

// ─── Users (miembros como usuarios planos [{id, name, email}]) ────────────────

interface MembersUsersResponseI extends ResponseBaseI {
  items: { id: number; name: string; email: string }[];
}

export const membersAsUsers = async (projectId: number) => {
  try {
    const { data } = await apiWithToken.get<MembersUsersResponseI>(`/projects/${projectId}/members/users`);
    return {
      status: true,
      message: data.message,
      items: data.items,
    };
  } catch (error) {
    return { status: false, message: "Error en el servidor", items: [] as { id: number; name: string; email: string }[] };
  }
};

// ─── Suspend ──────────────────────────────────────────────────────────────────

export const suspend = async (projectId: number, userId: number) => {
  try {
    const { data } = await apiWithToken.patch<MemberResponseI>(`/projects/${projectId}/members/${userId}/suspend`);
    return {
      status: true,
      message: data.message,
      items: data.items,
    };
  } catch (error) {
    return { status: false, message: "Error en el servidor" };
  }
};

// ─── Unsuspend ────────────────────────────────────────────────────────────────

export const unsuspend = async (projectId: number, userId: number) => {
  try {
    const { data } = await apiWithToken.patch<MemberResponseI>(`/projects/${projectId}/members/${userId}/unsuspend`);
    return {
      status: true,
      message: data.message,
      items: data.items,
    };
  } catch (error) {
    return { status: false, message: "Error en el servidor" };
  }
};

// ─── Destroy ──────────────────────────────────────────────────────────────────

export const destroy = async (projectId: number, userId: number) => {
  try {
    const { data } = await apiWithToken.delete<ResponseBaseI>(`/projects/${projectId}/members/${userId}`);
    return {
      status: true,
      message: data.message,
    };
  } catch (error) {
    return { status: false, message: "Error en el servidor" };
  }
};
