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
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        // Eliminar tokens existentes
        $user->tokens()->delete();

        // Crear nuevo token con expiración de 24 horas
        $token = $user->createToken('auth-token', ['*'], now()->addDay())->plainTextToken;

        // Cargar centros de trabajo asignados
        $user->load('workCenters.line');

        return response()->json([
            'user' => $user,
            'token' => $token,
            'work_centers' => $user->workCenters,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente'
        ]);
    }

    public function workCenters(Request $request)
    {
        $user = $request->user();
        $user->load('workCenters.line');

        return response()->json([
            'user' => $user,
            'work_centers' => $user->workCenters,
        ]);
    }
}
