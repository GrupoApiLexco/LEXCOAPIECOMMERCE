<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Services\UserService;
use App\Validators\UserValidator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    public function index(): JsonResponse
    {
        try {
            return response()->json(User::all());
        } catch (\Exception $e) {
            Log::error("Error fetching users: " . $e->getMessage());
            return $this->errorResponse('Error al obtener usuarios');
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validator = UserValidator::validateCreate($request->all());
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            $user = $this->userService->createUser($request->all());
            return response()->json($user, 201);
            
        } catch (\Exception $e) {
            Log::error("User creation error: " . $e->getMessage());
            return $this->errorResponse('Error al crear usuario', 500);
        }
    }

    public function show(User $user): JsonResponse
    {
        try {
            return response()->json($user);
        } catch (\Exception $e) {
            Log::error("Error fetching user: " . $e->getMessage());
            return $this->errorResponse('Usuario no encontrado', 404);
        }
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validator = UserValidator::validateUpdate($request->all());
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            $updatedUser = $this->userService->updateUser($user, $request->all());
            return response()->json($updatedUser);
            
        } catch (\Exception $e) {
            Log::error("User update error: " . $e->getMessage());
            return $this->errorResponse('Error al actualizar usuario', 500);
        }
    }

    public function toggleStatus(User $user): JsonResponse
    {
        try {
            $updatedUser = $this->userService->toggleStatus($user);
            return response()->json($updatedUser);
            
        } catch (\Exception $e) {
            Log::error("Status toggle error: " . $e->getMessage());
            return $this->errorResponse('Error al cambiar estado', 500);
        }
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $validator = UserValidator::validateImage($request->all());
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            $path = $this->userService->uploadImage($request->user(), $request->file('image'));
            return response()->json(['path' => $path]);
            
        } catch (\Exception $e) {
            Log::error("Image upload error: " . $e->getMessage());
            return $this->errorResponse('Error al subir imagen', 500);
        }
    }

    private function errorResponse(string $message, int $code = 500): JsonResponse
    {
        return response()->json([
            'error' => $message,
            'code' => $code
        ], $code);
    }
}
