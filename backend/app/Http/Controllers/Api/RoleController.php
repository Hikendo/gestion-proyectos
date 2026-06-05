<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProjectMemberRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * GET /api/roles
     */
    public function index(Request $request): JsonResponse
    {
        try {
            abort_if($request->user() === null, 403);

            $items = collect(ProjectMemberRole::cases())->map(fn(ProjectMemberRole $role) => [
                'name'        => $role->value,
                'label'       => $role->label(),
                'permissions' => $role->permissions(),
            ]);

            return response()->json([
                'status'  => true,
                'items'   => $items,
                'message' => 'Roles encontrados.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * GET /api/permissions
     * Devuelve todos los permisos registrados en Spatie para que el
     * super-admin pueda asignarlos/revocarlos desde el frontend.
     */
    public function permissions(Request $request): JsonResponse
    {
        try {
            abort_if($request->user() === null, 403);

            $items = \Spatie\Permission\Models\Permission::query()
                ->orderBy('name')
                ->get(['id', 'name']);

            return response()->json([
                'status'  => true,
                'items'   => $items,
                'message' => 'Permisos encontrados.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }
}
