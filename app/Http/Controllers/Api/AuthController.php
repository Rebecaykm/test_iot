<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

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

        $token = $user->createToken(
            'auth-token',
            ['*'],
            now()->addHours(24)
        )->plainTextToken;

        $tokenModel = $user->tokens()->latest()->first();

        return response()->json([
            'user' => $user,
            'token' => $token,
            'work_centers' => $user->workCenters,
            'expires_at' => $tokenModel->expires_at->toISOString(),
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
        $isExpiringSoon = $token->expires_at && $token->expires_at->diffInMinutes(now()) < 30;

        return response()->json([
            'user' => $user,
            'token_expires_soon' => $isExpiringSoon,
            'expires_at' => $token->expires_at?->toISOString(),
        ]);
    }

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

        $tokenModel = $user->tokens()->latest()->first();

        return response()->json([
            'token' => $token,
            'expires_at' => $tokenModel->expires_at->toISOString(),
            'message' => 'Token renovado exitosamente'
        ]);
    }

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
