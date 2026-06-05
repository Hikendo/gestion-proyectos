<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * GET /api/users
     */
    public function all(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        try {
            $items = User::orderBy('name')->get(['id', 'name', 'email']);

            return response()->json([
                'status'  => true,
                'items'   => $items,
                'message' => 'Usuarios encontrados.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        try {
            $search = $request->string('query', '');

            $query = User::query()
                ->with('roles:name')
                ->when($request->role, fn($q, $r) => $q->role($r));

            if ($search && $search->isNotEmpty()) {
                $term = '%' . $search->value() . '%';
                $query->where(fn($q) => $q->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term));
            }

            $items = $query->orderBy('name')->paginate(20);
            $items->getCollection()->transform(fn($u) => array_merge($u->toArray(), ['roles' => $u->getRoleNames()]));

            return response()->json([
                'status'  => true,
                'items'   => $items,
                'message' => 'Usuarios encontrados.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * POST /api/users
     * Usado por admin/PM para crear usuarios del sistema.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        try {
            $item = User::create([
                'name'     => $request->validated('name'),
                'email'    => $request->validated('email'),
                'password' => Hash::make($request->validated('password')),
            ]);

            if ($role = $request->validated('role')) {
                $item->assignRole($role);
            }

            return response()->json([
                'status'  => true,
                'items'   => array_merge($item->toArray(), ['roles' => $item->getRoleNames()]),
                'message' => 'Usuario creado.',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * GET /api/users/{user}
     */
    public function show(Request $request, User $user): JsonResponse
    {
        $this->authorize('view', $user);

        try {
            $user->load(['metrics']);

            return response()->json([
                'status'  => true,
                'items'   => array_merge(
                    $user->toArray(),
                    [
                        'roles'       => $user->getRoleNames(),
                        'permissions' => $user->getAllPermissions()->pluck('name'),
                    ]
                ),
                'message' => 'Usuario encontrado.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * PUT /api/users/{user}
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        try {
            $isAdmin = $request->user()->hasRole('super-admin');
            $data    = $request->validated();

            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            $role       = $data['role'] ?? null;
            $roleIsSet  = array_key_exists('role', $data);
            unset($data['role']);

            $user->update($data);

            if ($isAdmin && $roleIsSet) {
                $globalRoles = ['super-admin', 'project-manager'];
                $user->removeRole($globalRoles);
                if ($role) {
                    $user->assignRole($role);
                }
            }

            // Sincronizar permisos directos (solo super-admin)
            if ($isAdmin && array_key_exists('permissions', $data)) {
                $permissions = $data['permissions'] ?? [];
                $user->syncPermissions($permissions);
            }

            $user->refresh();

            return response()->json([
                'status'  => true,
                'items'   => array_merge(
                    $user->toArray(),
                    [
                        'roles'       => $user->getRoleNames(),
                        'permissions' => $user->getAllPermissions()->pluck('name'),
                    ]
                ),
                'message' => 'Usuario actualizado.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/users/{user}
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()->id === $user->id) {
            return response()->json(['status' => false, 'items' => null, 'message' => 'No puedes eliminarte a ti mismo.'], 422);
        }

        try {
            $user->delete();

            return response()->json(['status' => true, 'items' => null, 'message' => 'Usuario eliminado.']);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * GET /api/users/{user}/metrics
     */
    public function metrics(Request $request, User $user): JsonResponse
    {
        try {
            return response()->json([
                'status'  => true,
                'items'   => [
                    'assigned_tasks'    => $user->assignedTasks()->count(),
                    'completed_tasks'   => $user->assignedTasks()->where('status', 'done')->count(),
                    'worked_hours'      => $user->taskTimeLogs()->sum('hours'),
                    'performance_score' => $user->metrics?->performance_score ?? 0,
                ],
                'message' => 'M\u00e9tricas encontradas.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }
}
