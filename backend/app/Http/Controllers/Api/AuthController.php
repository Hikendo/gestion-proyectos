<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email'    => ['required', 'email'],
                'password' => ['required', 'string'],
            ]);

            $user = User::where('email', $request->email)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['Credenciales incorrectas.'],
                ]);
            }

            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'status'  => true,
                'items'   => [
                    'token' => $token,
                    'user'  => [
                        'id'          => $user->id,
                        'name'        => $user->name,
                        'email'       => $user->email,
                        'roles'       => $user->getRoleNames(),
                        'permissions' => $user->getAllPermissions()->pluck('name'),
                    ],
                ],
                'message' => 'Inicio de sesión correcto.',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $token = $request->user()->currentAccessToken();

            if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
                $token->delete();
            }

            return response()->json(['status' => true, 'items' => null, 'message' => 'Sesi\u00f3n cerrada correctamente.']);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * GET /api/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->load(['roles', 'metrics']);

            return response()->json([
                'status'  => true,
                'items'   => [
                    'id'          => $user->id,
                    'name'        => $user->name,
                    'email'       => $user->email,
                    'roles'       => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                    'metrics'     => $user->metrics,
                ],
                'message' => 'Perfil cargado.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * POST /api/auth/refresh-permissions
     *
     * Devuelve los permisos frescos del usuario autenticado.
     * Se usa después de que el frontend recibe un FCM `permissions_updated`.
     */
    public function refreshPermissions(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json(['status' => false, 'items' => null, 'message' => 'No autenticado.'], 401);
            }

            // Forzar recarga de permisos desde BD (limpia cache de Spatie para este usuario)
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            return response()->json([
                'status'  => true,
                'items'   => $user->getAllPermissions()->pluck('name'),
                'message' => 'Permisos actualizados.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * POST /api/auth/register
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
            ]);

            return response()->json([
                'status'  => true,
                'items'   => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                ],
                'message' => 'Usuario creado.',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }
}
