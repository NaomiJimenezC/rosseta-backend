<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{

    /**
     * Autentica al usuario y retorna un token de acceso.
     * - Valida las credenciales (email y password).
     * - Intenta autenticación y, si es exitosa, retorna el token.
     */
    public function login(Request $request)
    {
        // Validación de credenciales de inicio de sesión
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        // Retorna errores de validación si existen
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Extrae las credenciales del request
        $credentials = $request->only('email', 'password');

        // Intenta autenticar al usuario con las credenciales proporcionadas
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            // Retorna el token de acceso generado para el usuario
            return $this->respondWithToken($user);
        } else {
            // Retorna error si las credenciales son inválidas
            return response()->json(['error' => 'invalid_credentials'], 400);
        }
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|unique:users,username',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone_number' => 'nullable|string',
            'birthday' => 'nullable|date',
            'profile_picture_url' => 'nullable|string|url',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = User::create([
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone_number' => $request->phone_number,
                'birthday' => $request->birthday,
                'profile_picture_url' => $request->profile_picture_url,
                'is_2fa_enabled' => false,
            ]);

            // Generar código de verificación
            $verificationCode = Str::random(6);

            // Guardar el código de verificación en el usuario
            $user->verification_code = $verificationCode;
            $user->save();

            // Enviar correo electrónico con el código de verificación
            Mail::to($user->email)->send(new VerificationCodeMail($verificationCode));

            return response()->json(['message' => 'Usuario registrado exitosamente. Se ha enviado un código de verificación a tu correo electrónico.', 'user' => $user], 201);

        } catch (\Exception $e) {
            // Log the error
            Log::error('Error al registrar usuario: ' . $e->getMessage());
            return response()->json(['message' => $e], 500);
        }
    }

    public function verifyEmail(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'verification_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Correo electrónico no encontrado.'], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Este correo electrónico ya ha sido verificado.'], 200);
        }

        if ($user->verification_code === $request->verification_code) {
            $user->email_verified_at = now();
            $user->verification_code = null; // Limpiar el código después de la verificación
            $user->save();

            return response()->json(['message' => 'Correo electrónico verificado exitosamente.'], 200);
        } else {
            return response()->json(['message' => 'El código de verificación no es válido.'], 400);
        }
    }

    /**
     * Cierra la sesión del usuario eliminando sus tokens de acceso.
     */
    public function logout(Request $request)
    {
        // Elimina todos los tokens asociados al usuario autenticado
        $request->user()->tokens()->delete();

        // Retorna mensaje confirmando el cierre de sesión
        return response()->json(['message' => 'Sesión cerrada correctamente'], 200);
    }


    /**
     * Retorna la respuesta con el token de acceso.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($user)
    {
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'user' => $user
        ]);
    }
}
