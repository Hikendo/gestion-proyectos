<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserMetricResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserMetric;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * GET /api/users
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with('roles:name')
            ->when($request->role,   fn($q, $r) => $q->role($r))
            ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%"))
            ->paginate(20);

        return UserResource::collection($users)->response();
    }

    /**
     * POST /api/users
     * Usado por admin/PM para crear usuarios del sistema.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $user = User::create([
            'name'     => $request->validated('name'),
            'email'    => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
        ]);

        $user->assignRole($request->validated('role'));

        return UserResource::make($user->load('roles:name'))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/users/{user}
     */
    public function show(Request $request, User $user): JsonResponse
    {
        $this->authorize('view', $user);

        $user->load(['roles:name', 'metrics']);

        return UserResource::make($user)->response();
    }

    /**
     * PUT /api/users/{user}
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $isAdmin = $request->user()->hasRole('super-admin');
        $data    = $request->validated();

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $role = $data['role'] ?? null;
        unset($data['role']);

        $user->update($data);

        if ($role && $isAdmin) {
            $user->syncRoles([$role]);
        }

        return UserResource::make($user->load('roles:name'))->response();
    }

    /**
     * DELETE /api/users/{user}
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        // Impedir que el admin se elimine a sí mismo
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'No puedes eliminarte a ti mismo.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'Usuario eliminado.']);
    }

    /**
     * GET /api/users/{user}/metrics
     */
    public function metrics(Request $request, User $user): JsonResponse
    {
        // Cambiar 201 → 200
        return response()->json([
            'data' => [
                'assigned_tasks'    => $user->assignedTasks()->count(),
                'completed_tasks'   => $user->assignedTasks()->where('status', 'done')->count(),
                'worked_hours'      => $user->taskTimeLogs()->sum('hours'),
                'performance_score' => $user->metrics?->performance_score ?? 0,
            ],
        ], 200); // ← asegurar 200
    }
}
