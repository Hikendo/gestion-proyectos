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
            abort_if($request->user() === null, 403);

            $roles = collect(ProjectMemberRole::cases())->map(fn (ProjectMemberRole $role) => [
                'name'        => $role->value,
                'label'       => $role->label(),
                'permissions' => $role->permissions(),
            ]);

        return response()->json($roles);
    }
}
