import { apiWithToken } from "@/services/http";
import { ResponseBaseI } from "@/interfaces/ResponseBaseI";
import { ProjectMemberRole } from "@/interfaces/enums";

// ─── Index ────────────────────────────────────────────────────────────────────

export interface RoleI {
  name: ProjectMemberRole;
  label: string;
  permissions: string[];
}

interface RolesResponseI extends ResponseBaseI {
  items: RoleI[];
}

export const index = async () => {
  try {
    const { data } = await apiWithToken.get<RolesResponseI>("/roles");
    return {
      status: true,
      message: data.message,
      items: data.items,
    };
  } catch (error) {
    return { status: false, message: "Error en el servidor" };
  }
};
