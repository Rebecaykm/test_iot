<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

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

        // Eliminar tokens existentes
        $user->tokens()->delete();

        $token = $user->createToken(
            'auth-token',
            ['*'],
            now()->addHours(24)
        )->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'work_centers' => $user->workCenters,
            'expires_at' => now()->addHours(24)->toISOString(),
        ]);
    }

    public function logout(Request $request)
    {
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
        $isExpiringSoon = $token->expires_at && $token->expires_at->diffInHours(now()) < 2;

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

        $request->user()->currentAccessToken()->delete();

        $token = $user->createToken(
            'auth-token',
            ['*'],
            now()->addHours(24)
        )->plainTextToken;

        return response()->json([
            'token' => $token,
            'expires_at' => now()->addHours(24)->toISOString(),
            'message' => 'Token renovado exitosamente'
        ]);
    }
}
