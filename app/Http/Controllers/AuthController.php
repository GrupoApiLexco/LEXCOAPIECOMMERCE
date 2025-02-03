<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Validators\UserValidator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Exceptions\InvalidCredentialsException;
use App\Exceptions\InactiveUserException;

class AuthController extends Controller
{
    public function test()
    {
        return response()->json([
            'version' => config('app.api_version', '1.0.0'),
            'status' => 'API operativa'
        ]);
    }

    public function login(Request $request)
{
    $validator = UserValidator::validateLogin($request->all());
    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);  // Return 1/3 (validación)
    }

    $response = null;

    try {
        if (!Auth::attempt($request->only('email', 'password'))) {
            throw new InvalidCredentialsException();
        }

        $user = User::where('email', $request->email)
            ->where('status', User::STATUS_ACTIVE)
            ->firstOrFail();

        $response = response()->json([  // Asignación de respuesta exitosa
            'token' => $user->createToken('auth_token')->plainTextToken
        ]);

    } catch (\Exception $e) {
        $response = $this->handleLoginException($e);  // Unificado en 1 método
    }

    return $response;  // Return 2/3 (único punto de salida post-procesamiento)
}

private function handleLoginException(\Exception $e)
{
    Log::error("Login Error: {$e->getMessage()}");

    return match(get_class($e)) {
        InvalidCredentialsException::class => response()->json(
            ['error' => $e->getMessage()],
            $e->getCode()
        ),
        InactiveUserException::class => response()->json(
            ['error' => $e->getMessage()],
            $e->getCode()
        ),
        default => response()->json(
            ['error' => 'Error interno del servidor'],
            500
        )
    };
}
}
