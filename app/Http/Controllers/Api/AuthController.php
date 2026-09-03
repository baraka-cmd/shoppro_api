<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\UpdateProfileRequest;
use App\Http\Requests\Api\Auth\ChangePasswordRequest;
use App\Http\Requests\Api\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\Auth\ResetPasswordRequest;
use App\Http\Resources\Api\Auth\UserResource;
use App\Http\Resources\Api\Auth\AuthResource;
use App\Models\User;
use App\Models\User\UserProfile;
use App\Models\User\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\RefreshToken;


class AuthController extends Controller
{
    /**
     * Inscription d'un nouvel utilisateur
     * 
     * @param RegisterRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterRequest $request)
{
    try {
        // Créer l'utilisateur
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Créer le profil utilisateur
        $user->profile()->create([
            'first_name' => $request->first_name ?? null,
            'last_name' => $request->last_name ?? null,
            'phone' => $request->phone ?? null,
            'language' => 'fr',
            'currency' => 'FCFA',
        ]);

        // Assigner le rôle client par défaut
        $clientRole = Role::where('name', 'client')->first();
        if ($clientRole) {
            $user->roles()->attach($clientRole->id);
        }

        // Créer le token d'accès
        $token = $user->createAuthToken('auth_token');

        // Créer le refresh token
        $refreshToken = RefreshToken::create([
            'user_id' => $user->id,
            'token' => RefreshToken::generateToken(),
            'device_name' => $request->device_name ?? 'unknown',
            'device_type' => $request->device_type ?? 'mobile',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'expires_at' => now()->addDays(30),
            'is_active' => true,
        ]);

        // Charger les relations
        $user->load('profile', 'roles');

        return response()->json([
            'success' => true,
            'message' => 'Inscription réussie',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
                'refresh_token' => $refreshToken->token,
                'expires_at' => $refreshToken->expires_at->toISOString(),
            ],
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de l\'inscription',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    /**
     * Connexion d'un utilisateur
     * 
     * @param LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
{
    try {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants incorrects',
                'errors' => [
                    'email' => ['Email ou mot de passe incorrect'],
                ],
            ], 401);
        }

        // Révoquer les anciens tokens si "remember_me" est false
        if (!$request->remember_me) {
            $user->tokens()->delete();
            RefreshToken::where('user_id', $user->id)->active()->get()->each->revoke();
        }

        // Créer le token d'accès
        $token = $user->createAuthToken('auth_token');

        // Créer le refresh token
        $refreshToken = RefreshToken::create([
            'user_id' => $user->id,
            'token' => RefreshToken::generateToken(),
            'device_name' => $request->device_name ?? 'unknown',
            'device_type' => $request->device_type ?? 'mobile',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'expires_at' => now()->addDays(30),
            'is_active' => true,
        ]);

        $user->load('profile', 'roles');

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
                'refresh_token' => $refreshToken->token,
                'expires_at' => now()->addDays(30)->toISOString(),
            ],
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la connexion',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    /**
     * Déconnexion (révoquer le token courant)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            // Révoquer le token courant
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Déconnexion réussie',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la déconnexion',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Déconnexion de tous les appareils
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logoutAll(Request $request)
    {
        try {
            // Révoquer tous les tokens
            $request->user()->revokeAllTokens();

            return response()->json([
                'success' => true,
                'message' => 'Déconnexion de tous les appareils réussie',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la déconnexion',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Afficher le profil de l'utilisateur connecté
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile(Request $request)
    {
        try {
            $user = $request->user()->load('profile', 'roles');

            return response()->json([
                'success' => true,
                'message' => 'Profil récupéré avec succès',
                'data' => new UserResource($user),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du profil',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour le profil de l'utilisateur
     * 
     * @param UpdateProfileRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(UpdateProfileRequest $request)
{
    try {
        $user = $request->user();

        // Mettre à jour les informations de base
        if ($request->has('name')) {
            $user->name = $request->name;
        }
        $user->save();

        // Mettre à jour le profil détaillé
        $profileData = $request->validatedProfileData();
        $profileData = array_filter($profileData, function ($value) {
            return $value !== null;
        });

        // ✅ S'assurer que l'adresse est incluse
        if ($request->has('address')) {
            $profileData['address'] = $request->address;
        }
        if ($request->has('language')) {
            $profileData['language'] = $request->language;
        }
        if ($request->has('currency')) {
            $profileData['currency'] = $request->currency;
        }

        if ($user->profile) {
            $user->profile->update($profileData);
        } else {
            $user->profile()->create($profileData);
        }

        $user->load('profile', 'roles');

        return response()->json([
            'success' => true,
            'message' => 'Profil mis à jour avec succès',
            'data' => new UserResource($user),
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la mise à jour du profil',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    /**
     * Changer le mot de passe
     * 
     * @param ChangePasswordRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        try {
            $user = $request->user();

            // Vérifier le mot de passe actuel
            if (!$request->isCurrentPasswordValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mot de passe actuel incorrect',
                    'errors' => [
                        'current_password' => ['Le mot de passe actuel est incorrect'],
                    ],
                ], 422);
            }

            // Mettre à jour le mot de passe
            $user->update([
                'password' => Hash::make($request->new_password),
            ]);

            // Révoquer tous les tokens sauf le courant
            $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe changé avec succès',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de mot de passe',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Envoyer le lien de réinitialisation du mot de passe
     * 
     * @param ForgotPasswordRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'success' => true,
                    'message' => 'Lien de réinitialisation envoyé par email',
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Impossible d\'envoyer le lien de réinitialisation',
                'errors' => [
                    'email' => [__($status)],
                ],
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du lien',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Réinitialiser le mot de passe
     * 
     * @param ResetPasswordRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (User $user, string $password) {
                    $user->forceFill([
                        'password' => Hash::make($password),
                    ])->setRememberToken(Str::random(60));

                    $user->save();

                    // Révoquer tous les tokens
                    $user->revokeAllTokens();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return response()->json([
                    'success' => true,
                    'message' => 'Mot de passe réinitialisé avec succès',
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Impossible de réinitialiser le mot de passe',
                'errors' => [
                    'token' => [__($status)],
                ],
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réinitialisation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload de la photo de profil
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadAvatar(Request $request)
{
    try {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $user = $request->user();

        // Supprimer l'ancien avatar
        if ($user->profile && $user->profile->avatar) {
            Storage::disk('public')->delete($user->profile->avatar);
        }

        // Uploader le nouveau avatar
        $path = $request->file('avatar')->store('avatars', 'public');

        // Mettre à jour le profil
        if ($user->profile) {
            $user->profile->update(['avatar' => $path]);
        } else {
            $user->profile()->create(['avatar' => $path]);
        }

        // Recharger avec les relations
        $user->load('profile', 'roles');

        return response()->json([
            'success' => true,
            'message' => 'Photo de profil mise à jour',
            'data' => [
                'user' => new UserResource($user),
                'avatar_url' => asset('storage/' . $path),
            ],
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de l\'upload de la photo',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    /**
     * Vérifier la validité du token
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkToken(Request $request)
    {
        try {
            $user = $request->user()->load('profile', 'roles');

            return response()->json([
                'success' => true,
                'message' => 'Token valide',
                'data' => new UserResource($user),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide',
                'error' => $e->getMessage(),
            ], 401);
        }
    }

    /**
     * Vérifier l'email de l'utilisateur
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyEmail(Request $request)
    {
        try {
            $user = User::findOrFail($request->id);

            if ($user->hasVerifiedEmail()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Email déjà vérifié',
                ], 200);
            }

            if ($user->markEmailAsVerified()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Email vérifié avec succès',
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Impossible de vérifier l\'email',
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Renvoyer le lien de vérification
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resendVerification(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email',
            ]);

            $user = User::where('email', $request->email)->first();

            if ($user->hasVerifiedEmail()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Email déjà vérifié',
                ], 200);
            }

            $user->sendEmailVerificationNotification();

            return response()->json([
                'success' => true,
                'message' => 'Lien de vérification renvoyé',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Liste des utilisateurs (admin uniquement)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function listUsers(Request $request)
    {
        try {
            $users = User::with('profile', 'roles')
                        ->paginate($request->per_page ?? 15);

            return response()->json([
                'success' => true,
                'message' => 'Liste des utilisateurs récupérée',
                'data' => UserResource::collection($users),
                'pagination' => [
                    'total' => $users->total(),
                    'per_page' => $users->perPage(),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Afficher un utilisateur (admin uniquement)
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function showUser(int $id)
    {
        try {
            $user = User::with('profile', 'roles')->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur récupéré',
                'data' => new UserResource($user),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur introuvable',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Mettre à jour un utilisateur (admin uniquement)
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUser(Request $request, int $id)
    {
        try {
            $user = User::findOrFail($id);

            $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $id,
                'is_active' => 'sometimes|boolean',
            ]);

            $user->update($request->only(['name', 'email', 'is_active']));

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur mis à jour',
                'data' => new UserResource($user->load('profile', 'roles')),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer un utilisateur (admin uniquement)
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteUser(int $id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Révoquer tous les tokens
            $user->revokeAllTokens();
            
            // Supprimer l'utilisateur (soft delete)
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur supprimé',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Changer le rôle d'un utilisateur (admin uniquement)
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeUserRole(Request $request, int $id)
    {
        try {
            $user = User::findOrFail($id);

            $request->validate([
                'roles' => 'required|array',
                'roles.*' => 'exists:roles,name',
            ]);

            // Synchroniser les rôles
            $roleIds = Role::whereIn('name', $request->roles)->pluck('id')->toArray();
            $user->roles()->sync($roleIds);

            return response()->json([
                'success' => true,
                'message' => 'Rôles mis à jour',
                'data' => new UserResource($user->load('profile', 'roles')),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de rôle',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

   


/**
 * Rafraîchir le token expiré
 * 
 * @param Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function refreshToken(Request $request)
{
    try {
        $request->validate([
            'refresh_token' => 'required|string',
        ]);

        // Trouver le refresh token
        $refreshToken = RefreshToken::where('token', $request->refresh_token)
                                    ->active()
                                    ->notExpired()
                                    ->first();

        if (!$refreshToken) {
            return response()->json([
                'success' => false,
                'message' => 'Refresh token invalide ou expiré',
            ], 401);
        }

        // Récupérer l'utilisateur
        $user = $refreshToken->user;

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur introuvable',
            ], 404);
        }

        // Révoquer l'ancien token
        $refreshToken->markAsUsed();

        // Créer un nouveau token d'accès
        $newAccessToken = $user->createAuthToken('auth_token');

        // Créer un nouveau refresh token
        $newRefreshToken = RefreshToken::create([
            'user_id' => $user->id,
            'token' => RefreshToken::generateToken(),
            'device_name' => $request->device_name ?? $refreshToken->device_name,
            'device_type' => $request->device_type ?? $refreshToken->device_type,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'expires_at' => now()->addDays(30),
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Token rafraîchi avec succès',
            'data' => [
                'token' => $newAccessToken,
                'refresh_token' => $newRefreshToken->token,
                'expires_at' => now()->addDays(30)->toISOString(),
            ],
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du rafraîchissement du token',
            'error' => $e->getMessage(),
        ], 500);
    }
}

/**
 * Révoquer un refresh token
 * 
 * @param Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function revokeRefreshToken(Request $request)
{
    try {
        $request->validate([
            'refresh_token' => 'required|string',
        ]);

        $refreshToken = RefreshToken::where('token', $request->refresh_token)->first();

        if (!$refreshToken) {
            return response()->json([
                'success' => false,
                'message' => 'Refresh token introuvable',
            ], 404);
        }

        $refreshToken->revoke();

        return response()->json([
            'success' => true,
            'message' => 'Refresh token révoqué',
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la révocation',
            'error' => $e->getMessage(),
        ], 500);
    }
}

/**
 * Révoquer tous les refresh tokens d'un utilisateur
 * 
 * @param Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function revokeAllRefreshTokens(Request $request)
{
    try {
        $refreshTokens = RefreshToken::where('user_id', $request->user()->id)
                                      ->active()
                                      ->get();

        foreach ($refreshTokens as $token) {
            $token->revoke();
        }

        return response()->json([
            'success' => true,
            'message' => 'Tous les refresh tokens ont été révoqués',
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la révocation',
            'error' => $e->getMessage(),
        ], 500);
    }
}
}