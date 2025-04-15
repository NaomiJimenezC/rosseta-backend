<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * Get the profile information of the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(): JsonResponse
    {
        return response()->json(Auth::user());
    }

    /**
     * Get the profile information of a specific user by ID or username.
     *
     * @param  string  $identifier The user's ID or username.
     * @return \Illuminate\Http\JsonResponse
     */
    public function showUser(string $identifier): JsonResponse
    {
        //$user = User::where('id', $identifier)->orWhere('username', $identifier)->first();
        $user = User::where('username', $identifier)->firstOrFail();
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        return response()->json($user);
    }

    /**
     * Update the profile information of the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'username' => 'nullable|string|unique:users,username,' . $user->id,
            'email' => 'nullable|string|email|unique:users,email,' . $user->id,
            'phone_number' => 'nullable|string',
            'birthday' => 'nullable|date',
            'profile_picture_url' => 'nullable|string|url',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->update($request->validated());

        return response()->json($user);
    }

    /**
     * Change the password of the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'La contraseña actual es incorrecta.'], 401);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json(['message' => 'Contraseña actualizada correctamente.']);
    }

    /**
     * Update the profile picture URL of the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfilePicture(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'profile_picture_url' => 'nullable|string|url|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->has('profile_picture_url')) {
            $user->profile_picture_url = $request->input('profile_picture_url');
            $user->save();

            return response()->json(['message' => 'URL de la foto de perfil actualizada correctamente.', 'profile_picture_url' => $user->profile_picture_url]);
        }

        return response()->json(['message' => 'No se proporcionó URL de la foto de perfil.'], 200);
    }

    /**
     * Delete the profile picture URL of the authenticated user (set it to null).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteProfilePicture(): JsonResponse
    {
        $user = Auth::user();

        $user->profile_picture_url = null;
        $user->save();

        return response()->json(['message' => 'URL de la foto de perfil eliminada correctamente.']);
    }

    /**
     * Delete the account of the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(): JsonResponse
    {
        $user = Auth::user();

        $user->delete();

        $user->tokens()->delete();

        return response()->json(['message' => 'Cuenta eliminada correctamente.']);
    }
}