<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * GET /api/roles
     */
    public function index(Request $request): JsonResponse
    {
        abort_if(
            ! $request->user()->hasRole(['super-admin', 'project-manager']),
            403
        );

        $roles = Role::with('permissions:name')
            ->get()
            ->map(fn($role) => [
                'name'        => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ]);

        return response()->json($roles);
    }
}
