<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\User\UserProfile;
use App\Models\User\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AdminUserController extends Controller
{
    // ============================================
    // LISTE
    // ============================================

    /**
     * Récupérer la liste paginée des utilisateurs.
     *
     * Endpoint : GET /api/v1/admin/users
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 20);
            $page = $request->query('page', 1);

            $query = User::with(['roles', 'profile'])
                ->orderByDesc('created_at');

            // Filtre recherche (nom ou email)
            if ($request->has('search') && !empty($request->query('search'))) {
                $search = $request->query('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%");
                });
            }

            // Filtre rôle
            if ($request->has('role') && !empty($request->query('role'))) {
                $role = $request->query('role');
                $query->whereHas('roles', function ($q) use ($role) {
                    $q->where('name', $role);
                });
            }

            // Filtre statut
            if ($request->has('status') && !empty($request->query('status'))) {
                $status = $request->query('status');
                if ($status == 'active') {
                    $query->where('is_active', true);
                } elseif ($status == 'inactive') {
                    $query->where('is_active', false);
                }
            }

            // Tri
            $sortBy = $request->query('sort_by', 'created_at');
            $sortDirection = $request->query('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            $users = $query->paginate($perPage, ['*'], 'page', $page);

            $data = $users->map(function ($user) {
                return $this->formatUser($user);
            });

            return response()->json([
                'success' => true,
                'message' => 'Utilisateurs récupérés avec succès',
                'data' => $data,
                'pagination' => [
                    'total' => $users->total(),
                    'count' => $users->count(),
                    'per_page' => $users->perPage(),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'has_more' => $users->hasMorePages(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des utilisateurs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // DÉTAILS
    // ============================================

    /**
     * Récupérer les détails d'un utilisateur.
     *
     * Endpoint : GET /api/v1/admin/users/{id}
     */
    public function show($id)
    {
        try {
            $user = User::with(['roles', 'profile', 'addresses', 'orders'])
                ->find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur introuvable',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur récupéré avec succès',
                'data' => $this->formatUserDetailed($user),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'utilisateur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer un utilisateur par son email.
     *
     * Endpoint : GET /api/v1/admin/users/by-email/{email}
     */
    public function showByEmail($email)
    {
        try {
            $user = User::with(['roles', 'profile'])
                ->where('email', $email)
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur introuvable',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur récupéré',
                'data' => $this->formatUserDetailed($user),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'utilisateur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les utilisateurs récents.
     *
     * Endpoint : GET /api/v1/admin/users/recent
     */
    public function recent(Request $request)
    {
        try {
            $limit = $request->query('limit', 10);

            $users = User::with(['roles', 'profile'])
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Utilisateurs récents récupérés',
                'data' => $users->map(function ($user) {
                    return $this->formatUser($user);
                }),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des utilisateurs récents',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // CRÉATION
    // ============================================

    /**
     * Créer un nouvel utilisateur (avec photo et profil complet).
     *
     * Endpoint : POST /api/v1/admin/users
     *
     * Body (multipart/form-data) :
     * {
     *   "name": "Jean Kamga",
     *   "email": "jean@example.com",
     *   "password": "password123",
     *   "password_confirmation": "password123",
     *   "role": "client",
     *   "phone": "+237 6 51 11 11 11",
     *   "country": "Cameroun",
     *   "city": "Douala",
     *   "address": "Akwa",
     *   "is_active": true,
     *   "avatar": <file>
     * }
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6|confirmed',
                'role' => 'nullable|string|in:admin,seller,client',
                'phone' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:100',
                'city' => 'nullable|string|max:100',
                'address' => 'nullable|string|max:255',
                'is_active' => 'nullable|boolean',
                'avatar' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
            ]);

            DB::beginTransaction();

            // 1. Créer l'utilisateur
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // 2. Upload de l'avatar
            // 2. Upload de l'avatar (le stockage se fait APRÈS le profil)
            $avatarPath = null;
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                // ❌ NE PAS faire $user->avatar = ...
                // L'avatar est stocké UNIQUEMENT dans user_profiles
            }

            // 3. Créer le profil
            // 3. Créer le profil
            $nameParts = explode(' ', $validated['name'], 2);
            UserProfile::create([
                'user_id' => $user->id,
                'first_name' => $nameParts[0] ?? null,
                'last_name' => $nameParts[1] ?? null,
                'phone' => $validated['phone'] ?? null,
                'country' => $validated['country'] ?? null,
                'city' => $validated['city'] ?? null,
                'address' => $validated['address'] ?? null,
                'avatar' => $avatarPath,   // ✅ Stocké ICI
                'language' => 'fr',
                'currency' => 'FCFA',
            ]);

            // 4. Assigner le rôle
            $roleName = $validated['role'] ?? 'client';
            $role = Role::where('name', $roleName)->first();

            if (!$role) {
                $role = Role::where('name', 'client')->first();
            }

            if ($role) {
                $user->roles()->attach($role->id);
            }

            DB::commit();

            // 5. Recharger
            $user->load(['roles', 'profile']);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur créé avec succès',
                'data' => $this->formatUserDetailed($user),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'utilisateur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // MODIFICATION
    // ============================================

    /**
     * Mettre à jour un utilisateur (avec photo et profil).
     *
     * Endpoint : PUT /api/v1/admin/users/{id}
     *
     * Body (multipart/form-data) :
     * {
     *   "_method": "PUT",
     *   "name": "Jean Kamga",
     *   "email": "jean@example.com",
     *   "phone": "...",
     *   "country": "...",
     *   "city": "...",
     *   "address": "...",
     *   "is_active": true,
     *   "role": "client",
     *   "avatar": <file>
     * }
     */
    public function update(Request $request, $id)
    {
        try {
            $user = User::with(['roles', 'profile'])->find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur introuvable',
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $id,
                'password' => 'nullable|string|min:6|confirmed',
                'phone' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:100',
                'city' => 'nullable|string|max:100',
                'address' => 'nullable|string|max:255',
                'is_active' => 'sometimes|boolean',
                'role' => 'nullable|string|in:admin,seller,client',
                'avatar' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
            ]);

            DB::beginTransaction();

            // 1. Mettre à jour l'utilisateur
            if (isset($validated['name'])) {
                $user->name = $validated['name'];
            }
            if (isset($validated['email'])) {
                $user->email = $validated['email'];
            }
            if (isset($validated['is_active'])) {
                $user->is_active = $validated['is_active'];
            }
            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            // 2. Upload du nouvel avatar
            // 2. Upload du nouvel avatar (stocké dans user_profiles)
            $avatarPath = null;
            if ($request->hasFile('avatar')) {
                if ($user->profile?->avatar && Storage::disk('public')->exists($user->profile->avatar)) {
                    Storage::disk('public')->delete($user->profile->avatar);
                }
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
            }
            $user->save();

            // 3. Mettre à jour le profil
            $profile = $user->profile;
            if (!$profile) {
                $profile = new UserProfile();
                $profile->user_id = $user->id;
            }

            if (isset($validated['name'])) {
                $nameParts = explode(' ', $validated['name'], 2);
                $profile->first_name = $nameParts[0] ?? null;
                $profile->last_name = $nameParts[1] ?? null;
            }
            if (isset($validated['phone'])) $profile->phone = $validated['phone'];
            if (isset($validated['country'])) $profile->country = $validated['country'];
            if (isset($validated['city'])) $profile->city = $validated['city'];
            if (isset($validated['address'])) $profile->address = $validated['address'];
            if ($avatarPath != null) $profile->avatar = $avatarPath;

            $profile->save();

            // 4. Changer le rôle
            if (!empty($validated['role'])) {
                $role = Role::where('name', $validated['role'])->first();
                if ($role) {
                    $user->roles()->sync([$role->id]);
                }
            }

            DB::commit();

            // 5. Recharger
            $user->load(['roles', 'profile']);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur mis à jour avec succès',
                'data' => $this->formatUserDetailed($user),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'utilisateur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // SUPPRESSION
    // ============================================

    /**
     * Supprimer un utilisateur (soft delete).
     *
     * Endpoint : DELETE /api/v1/admin/users/{id}
     */
    public function destroy($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur introuvable',
                ], 404);
            }

            // Empêcher la suppression de soi-même
            if (auth()->id() === $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas supprimer votre propre compte',
                ], 422);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur supprimé avec succès',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'utilisateur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restaurer un utilisateur supprimé.
     *
     * Endpoint : POST /api/v1/admin/users/{id}/restore
     */
    public function restore($id)
    {
        try {
            $user = User::withTrashed()->find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur introuvable',
                ], 404);
            }

            $user->restore();

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur restauré',
                'data' => $this->formatUser($user),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la restauration',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // RÔLES
    // ============================================

    /**
     * Changer le rôle d'un utilisateur.
     *
     * Endpoint : PUT /api/v1/admin/users/{id}/role
     */
    public function changeRole(Request $request, $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur introuvable',
                ], 404);
            }

            $validated = $request->validate([
                'role' => 'required|string|in:admin,seller,client',
            ]);

            $role = Role::where('name', $validated['role'])->first();

            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rôle introuvable',
                ], 404);
            }

            $user->roles()->sync([$role->id]);
            $user->load(['roles', 'profile']);

            return response()->json([
                'success' => true,
                'message' => 'Rôle changé avec succès',
                'data' => $this->formatUserDetailed($user),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de rôle',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // STATUTS
    // ============================================

    /**
     * Activer un utilisateur.
     *
     * Endpoint : POST /api/v1/admin/users/{id}/activate
     */
    public function activate($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur introuvable',
                ], 404);
            }

            $user->is_active = true;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur activé',
                'data' => $this->formatUser($user),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'activation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Désactiver un utilisateur.
     *
     * Endpoint : POST /api/v1/admin/users/{id}/deactivate
     */
    public function deactivate($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur introuvable',
                ], 404);
            }

            $user->is_active = false;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur désactivé',
                'data' => $this->formatUser($user),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la désactivation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // UPLOAD D'AVATAR (ENDPOINT DÉDIÉ)
    // ============================================

    /**
     * Upload l'avatar d'un utilisateur.
     *
     * Endpoint : POST /api/v1/admin/users/{id}/avatar
     */
    public function uploadAvatar(Request $request, $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur introuvable',
                ], 404);
            }

            $validated = $request->validate([
                'avatar' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
            ]);

            // Supprimer l'ancien avatar
            if ($user->profile?->avatar && Storage::disk('public')->exists($user->profile->avatar)) {
                Storage::disk('public')->delete($user->profile->avatar);
            }

            // Sauvegarder le nouveau
            $avatarPath = $request->file('avatar')->store('avatars', 'public');

            // Mettre à jour le profil (c'est LUI qui contient l'avatar)
            if ($user->profile) {
                $user->profile->avatar = $avatarPath;
                $user->profile->save();
            } else {
                UserProfile::create([
                    'user_id' => $user->id,
                    'avatar' => $avatarPath,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Avatar mis à jour',
                'data' => [
                    'avatar' => asset('storage/' . $avatarPath),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload de l\'avatar',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer l'avatar d'un utilisateur.
     *
     * Endpoint : DELETE /api/v1/admin/users/{id}/avatar
     */
    public function deleteAvatar($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur introuvable',
                ], 404);
            }

            // Supprimer l'avatar du profil
            if ($user->profile?->avatar) {
                if (Storage::disk('public')->exists($user->profile->avatar)) {
                    Storage::disk('public')->delete($user->profile->avatar);
                }
                $user->profile->avatar = null;
                $user->profile->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Avatar supprimé',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'avatar',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // STATISTIQUES
    // ============================================

    /**
     * Récupérer les statistiques des utilisateurs.
     *
     * Endpoint : GET /api/v1/admin/users/stats/count
     */
    public function statsCount()
    {
        try {
            $total = User::count();
            $active = User::where('is_active', true)->count();
            $inactive = User::where('is_active', false)->count();
            $admins = User::whereHas('roles', function ($q) {
                $q->where('name', 'admin');
            })->count();
            $sellers = User::whereHas('roles', function ($q) {
                $q->where('name', 'seller');
            })->count();
            $clients = User::whereHas('roles', function ($q) {
                $q->where('name', 'client');
            })->count();

            return response()->json([
                'success' => true,
                'message' => 'Statistiques récupérées',
                'data' => [
                    'total' => (int) $total,
                    'active' => (int) $active,
                    'inactive' => (int) $inactive,
                    'admins' => (int) $admins,
                    'sellers' => (int) $sellers,
                    'clients' => (int) $clients,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les utilisateurs par rôle.
     *
     * Endpoint : GET /api/v1/admin/users/stats/by-role
     */
    public function statsByRole()
    {
        try {
            $stats = [
                'admin' => User::whereHas('roles', function ($q) {
                    $q->where('name', 'admin');
                })->count(),
                'seller' => User::whereHas('roles', function ($q) {
                    $q->where('name', 'seller');
                })->count(),
                'client' => User::whereHas('roles', function ($q) {
                    $q->where('name', 'client');
                })->count(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Utilisateurs par rôle récupérés',
                'data' => $stats,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération par rôle',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // HELPERS PRIVÉS
    // ============================================

    /**
     * Formater un utilisateur (version simple).
     */
    /**
 * Formater un utilisateur (version simple — pour la liste).
 */
private function formatUser(User $user): array
{
    // Charger le profil si pas déjà chargé
    if (!$user->relationLoaded('profile')) {
        $user->load('profile');
    }

    // ✅ CALCUL EN TEMPS RÉEL depuis la table orders
    $ordersCount = \App\Models\Order\Order::where('user_id', $user->id)->count();
    $totalSpent = \App\Models\Order\Order::where('user_id', $user->id)->sum('total');

    return [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'avatar' => $this->resolveAvatarUrl($user),
        'email_verified_at' => $user->email_verified_at
            ? $user->email_verified_at->toIso8601String()
            : null,
        'roles' => $user->roles->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $role->display_name ?? $role->name,
            ];
        })->toArray(),
        'is_active' => (bool) ($user->is_active ?? true),

        // ✅ STATS calculées en temps réel
        'total_orders' => (int) $ordersCount,
        'total_spent' => (float) $totalSpent,
        'favorites_count' => $this->countFavorites($user),
        'reviews_count' => $this->countReviews($user),

        // ✅ CORRECTION : lire la vraie valeur depuis la DB
        'last_login_at' => $user->last_login_at
            ? $user->last_login_at->toIso8601String()
            : null,
        'last_activity_at' => $user->updated_at
            ? $user->updated_at->toIso8601String()
            : null,

        'created_at' => $user->created_at
            ? $user->created_at->toIso8601String()
            : null,
        'updated_at' => $user->updated_at
            ? $user->updated_at->toIso8601String()
            : null,
    ];
}

    /**
     * Formater un utilisateur détaillé (avec profil).
     */
    /**
 * Formater un utilisateur détaillé (avec profil et stats).
 */
private function formatUserDetailed(User $user): array
{
    // Charger les relations
    $user->load(['profile', 'roles', 'addresses']);

    // ✅ CALCUL EN TEMPS RÉEL depuis la table orders
    $orders = \App\Models\Order\Order::where('user_id', $user->id)->get();
    $ordersCount = $orders->count();
    $totalSpent = $orders->sum('total');
    $lastOrder = $orders->sortByDesc('created_at')->first();

    $data = $this->formatUser($user);

    // Override avec les vraies stats
    $data['total_orders'] = (int) $ordersCount;
    $data['total_spent'] = (float) $totalSpent;
    $data['last_order_at'] = $lastOrder?->created_at?->toIso8601String();

    // ✅ Profil détaillé
    if ($user->profile) {
        $data['profile'] = [
            'first_name' => $user->profile->first_name,
            'last_name' => $user->profile->last_name,
            'phone' => $user->profile->phone,
            'birth_date' => $user->profile->birth_date
                ? $user->profile->birth_date->toDateString()
                : null,
            'gender' => $user->profile->gender,
            'country' => $user->profile->country,
            'city' => $user->profile->city,
            'address' => $user->profile->address,
            'language' => $user->profile->language ?? 'fr',
            'currency' => $user->profile->currency ?? 'FCFA',
        ];

        // Champs à la racine (pour le Flutter)
        $data['first_name'] = $user->profile->first_name;
        $data['last_name'] = $user->profile->last_name;
        $data['phone'] = $user->profile->phone;
        $data['country'] = $user->profile->country;
        $data['city'] = $user->profile->city;
        $data['address'] = $user->profile->address;
        $data['language'] = $user->profile->language ?? 'fr';
        $data['currency'] = $user->profile->currency ?? 'FCFA';
    }

    // ✅ Adresses
    if ($user->addresses) {
        $data['addresses_count'] = $user->addresses->count();
    }

    // ✅ Favoris et avis
    $data['favorites_count'] = $this->countFavorites($user);
    $data['reviews_count'] = $this->countReviews($user);

    return $data;
}

    

    /**
     * Résoudre l'URL de l'avatar (depuis user_profiles).
     */
    private function resolveAvatarUrl(User $user): ?string
    {
        $avatar = $user->profile?->avatar;
        if (!$avatar) return null;
        if (str_starts_with($avatar, 'http')) return $avatar;
        return asset('storage/' . $avatar);
    }

    /**
 * Compter les favoris d'un utilisateur.
 */
private function countFavorites(User $user): int
{
    try {
        return \App\Models\Review\Favorite::where('user_id', $user->id)->count();
    } catch (\Exception $e) {
        return 0;
    }
}

/**
 * Compter les avis d'un utilisateur.
 */
private function countReviews(User $user): int
{
    try {
        return \App\Models\Review\Review::where('user_id', $user->id)->count();
    } catch (\Exception $e) {
        return 0;
    }
}
}