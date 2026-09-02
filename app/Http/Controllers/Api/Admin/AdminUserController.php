<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\User\UserProfile;
use App\Models\User\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    /**
     * Récupérer la liste paginée des utilisateurs.
     *
     * Endpoint : GET /api/v1/admin/users
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 20);
            $page = $request->query('page', 1);

            $query = User::with(['roles', 'profile'])
                ->orderByDesc('created_at');

            // Filtre par recherche (nom ou email)
            if ($request->has('search') && !empty($request->query('search'))) {
                $search = $request->query('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%");
                });
            }

            // Filtre par rôle
            if ($request->has('role') && !empty($request->query('role'))) {
                $role = $request->query('role');
                $query->whereHas('roles', function ($q) use ($role) {
                    $q->where('name', $role);
                });
            }

            // Filtre par statut
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
            $user = User::with('roles')
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
                'data' => $this->formatUser($user),
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

            $users = User::with('roles')
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

    /**
     * Créer un nouvel utilisateur.
     *
     * Endpoint : POST /api/v1/admin/users
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
                'role' => 'nullable|string|in:admin,seller,client',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_active' => true,
            ]);

            // Assigner le rôle
            if (isset($validated['role'])) {
                $role = Role::where('name', $validated['role'])->first();
                if ($role) {
                    $user->roles()->attach($role->id);
                }
            } else {
                $role = Role::where('name', 'client')->first();
                if ($role) {
                    $user->roles()->attach($role->id);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur créé avec succès',
                'data' => $this->formatUser($user),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'utilisateur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour un utilisateur.
     *
     * Endpoint : PUT /api/v1/admin/users/{id}
     */
    public function update(Request $request, $id)
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
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $id,
                'is_active' => 'sometimes|boolean',
            ]);

            $user->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur mis à jour avec succès',
                'data' => $this->formatUser($user),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'utilisateur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

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

            // Remplacer tous les rôles
            $user->roles()->sync([$role->id]);

            return response()->json([
                'success' => true,
                'message' => 'Rôle changé avec succès',
                'data' => $this->formatUser($user),
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

    /**
     * Formater un utilisateur pour la réponse API.
     */
    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar ?? null,
            'email_verified_at' => $user->email_verified_at ? $user->email_verified_at->toIso8601String() : null,
            'roles' => $user->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name' => $role->display_name ?? $role->name,
                ];
            })->toArray(),
            'is_active' => (bool) ($user->is_active ?? true),
            'total_orders' => (int) ($user->total_orders ?? 0),
            'total_spent' => (float) ($user->total_spent ?? 0),
            'created_at' => $user->created_at ? $user->created_at->toIso8601String() : null,
            'updated_at' => $user->updated_at ? $user->updated_at->toIso8601String() : null,
        ];
    }

    /**
     * Formater un utilisateur détaillé (avec profil).
     */
    private function formatUserDetailed(User $user): array
    {
        $data = $this->formatUser($user);

        if ($user->profile) {
            $data['profile'] = [
                'first_name' => $user->profile->first_name,
                'last_name' => $user->profile->last_name,
                'phone' => $user->profile->phone,
                'birth_date' => $user->profile->birth_date ? $user->profile->birth_date->toDateString() : null,
                'gender' => $user->profile->gender,
                'country' => $user->profile->country,
                'city' => $user->profile->city,
                'address' => $user->profile->address,
                'language' => $user->profile->language ?? 'fr',
                'currency' => $user->profile->currency ?? 'FCFA',
            ];
        }

        if ($user->addresses) {
            $data['addresses_count'] = $user->addresses->count();
        }

        if ($user->orders) {
            $data['orders_count'] = $user->orders->count();
            $data['total_spent'] = (float) $user->orders->sum('total');
        }

        return $data;
    }
}