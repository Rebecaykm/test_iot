<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->login)
            ->orWhere('nickname', $request->login)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        // NO eliminar tokens existentes (permite múltiples sesiones)
        // $user->tokens()->delete(); // COMENTA ESTA LÍNEA

        $token = $user->createToken(
            'auth-token',
            ['*'],
            now()->addHours(24)
        )->plainTextToken;

        // Obtener la fecha de expiración real del token
        $tokenModel = $user->tokens()->latest()->first();

        Log::info('login', [
            'user' => $user,
            'token' => $token,
            'work_centers' => $user->workCenters,
            'expires_at' => $tokenModel->expires_at->toISOString(),
        ]);

        return response()->json([
            'user' => $user,
            'token' => $token,
            'work_centers' => $user->workCenters,
            'expires_at' => $tokenModel->expires_at->toISOString(),
        ]);
    }

    public function logout(Request $request)
    {
        // Eliminar solo el token actual, no todos
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente'
        ]);
    }

    public function user(Request $request)
    {
        $user = $request->user();
        $user->load(['workCenters' => function ($query) {
            $query->with('line');
        }]);

        $token = $request->user()->currentAccessToken();
        $isExpiringSoon = $token->expires_at && $token->expires_at->diffInMinutes(now()) < 30;

        return response()->json([
            'user' => $user,
            'token_expires_soon' => $isExpiringSoon,
            'expires_at' => $token->expires_at?->toISOString(),
        ]);
    }

    /**
     * Obtener usuarios con rol de Escaneo
     */
    public function getScanUsers()
    {
        $users = User::role('Escaneo Usuario')
            ->select('nickname', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($users);
    }

    public function refreshToken(Request $request)
    {
        $user = $request->user();

        // Eliminar el token actual
        $request->user()->currentAccessToken()->delete();

        // Crear nuevo token
        $token = $user->createToken(
            'auth-token',
            ['*'],
            now()->addHours(24)
        )->plainTextToken;

        // Obtener la fecha de expiración
        $tokenModel = $user->tokens()->latest()->first();

        return response()->json([
            'token' => $token,
            'expires_at' => $tokenModel->expires_at->toISOString(),
            'message' => 'Token renovado exitosamente'
        ]);
    }

    /**
     * Nuevo método para verificar estado del token
     */
    public function checkToken(Request $request)
    {
        $token = $request->user()->currentAccessToken();

        return response()->json([
            'valid' => !$token->expires_at || $token->expires_at->isFuture(),
            'expires_at' => $token->expires_at?->toISOString(),
            'expires_in_minutes' => $token->expires_at ? $token->expires_at->diffInMinutes(now()) : null,
        ]);
    }
}
