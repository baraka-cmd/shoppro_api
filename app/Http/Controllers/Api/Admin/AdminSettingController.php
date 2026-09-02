<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminSettingController extends Controller
{
    /**
     * Récupérer tous les paramètres.
     *
     * Endpoint : GET /api/v1/admin/settings
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = Setting::query();

            // Filtres
            if ($request->has('group') && !empty($request->query('group'))) {
                $query->where('group', $request->query('group'));
            }

            if ($request->has('is_public')) {
                $query->where('is_public', $request->query('is_public') == 'true');
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->query('is_active') == 'true');
            }

            $settings = $query->orderBy('group')->orderBy('key')->get();

            return response()->json([
                'success' => true,
                'message' => 'Paramètres récupérés avec succès',
                'data' => $settings->map(function ($setting) {
                    return $this->formatSetting($setting);
                }),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des paramètres',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer tous les paramètres groupés.
     *
     * Endpoint : GET /api/v1/admin/settings/grouped
     */
    public function grouped()
    {
        try {
            $settings = Setting::orderBy('group')->orderBy('key')->get();

            $grouped = [];
            foreach ($settings as $setting) {
                $grouped[$setting->group][] = $this->formatSetting($setting);
            }

            return response()->json([
                'success' => true,
                'message' => 'Paramètres groupés récupérés',
                'data' => $grouped,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des paramètres groupés',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer un paramètre par sa clé.
     *
     * Endpoint : GET /api/v1/admin/settings/{key}
     */
    public function show($key)
    {
        try {
            $setting = Setting::where('key', $key)->first();

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paramètre introuvable',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Paramètre récupéré avec succès',
                'data' => $this->formatSetting($setting),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du paramètre',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les paramètres d'un groupe.
     *
     * Endpoint : GET /api/v1/admin/settings/group/{group}
     */
    public function byGroup($group)
    {
        try {
            $settings = Setting::where('group', $group)
                ->orderBy('key')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Paramètres du groupe récupérés',
                'data' => $settings->map(function ($setting) {
                    return $this->formatSetting($setting);
                }),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des paramètres du groupe',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les paramètres publics.
     *
     * Endpoint : GET /api/v1/admin/settings/public
     */
    public function publicSettings()
    {
        try {
            $settings = Setting::where('is_public', true)
                ->where('is_active', true)
                ->orderBy('group')
                ->orderBy('key')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Paramètres publics récupérés',
                'data' => $settings->map(function ($setting) {
                    return $this->formatSetting($setting);
                }),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des paramètres publics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Créer un nouveau paramètre.
     *
     * Endpoint : POST /api/v1/admin/settings
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'key' => 'required|string|max:100|unique:settings,key',
                'value' => 'nullable|string',
                'group' => 'nullable|string|max:100',
                'type' => 'nullable|string|in:string,integer,float,boolean,json,array,file',
                'description' => 'nullable|string',
                'is_public' => 'nullable|boolean',
                'is_active' => 'nullable|boolean',
            ]);

            $setting = Setting::create([
                'key' => $validated['key'],
                'value' => $validated['value'] ?? '',
                'group' => $validated['group'] ?? 'general',
                'type' => $validated['type'] ?? 'string',
                'description' => $validated['description'] ?? null,
                'is_public' => $validated['is_public'] ?? false,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Effacer le cache
            Cache::forget('settings_all');

            return response()->json([
                'success' => true,
                'message' => 'Paramètre créé avec succès',
                'data' => $this->formatSetting($setting),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du paramètre',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour un paramètre.
     *
     * Endpoint : PUT /api/v1/admin/settings/{key}
     */
    public function update(Request $request, $key)
    {
        try {
            $setting = Setting::where('key', $key)->first();

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paramètre introuvable',
                ], 404);
            }

            $validated = $request->validate([
                'value' => 'nullable|string',
                'group' => 'nullable|string|max:100',
                'type' => 'nullable|string|in:string,integer,float,boolean,json,array,file',
                'description' => 'nullable|string',
                'is_public' => 'nullable|boolean',
                'is_active' => 'nullable|boolean',
            ]);

            $setting->update($validated);

            // Effacer le cache
            Cache::forget('settings_all');
            Cache::forget('setting_' . $key);

            return response()->json([
                'success' => true,
                'message' => 'Paramètre mis à jour avec succès',
                'data' => $this->formatSetting($setting),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du paramètre',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour plusieurs paramètres en masse.
     *
     * Endpoint : PUT /api/v1/admin/settings/bulk-update
     */
    public function bulkUpdate(Request $request)
    {
        try {
            $settings = $request->input('settings', []);

            if (empty($settings)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun paramètre fourni',
                ], 422);
            }

            $count = 0;
            foreach ($settings as $item) {
                if (isset($item['key']) && isset($item['value'])) {
                    Setting::where('key', $item['key'])->update([
                        'value' => $item['value'],
                    ]);
                    $count++;
                }
            }

            // Effacer le cache
            Cache::forget('settings_all');

            return response()->json([
                'success' => true,
                'message' => "$count paramètres mis à jour",
                'data' => ['count' => $count],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour en masse',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour tous les paramètres d'un groupe.
     *
     * Endpoint : PUT /api/v1/admin/settings/group/{group}
     */
    public function updateGroup(Request $request, $group)
    {
        try {
            $settings = $request->input('settings', []);

            if (empty($settings)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun paramètre fourni',
                ], 422);
            }

            $count = 0;
            foreach ($settings as $item) {
                if (isset($item['key']) && isset($item['value'])) {
                    Setting::where('key', $item['key'])
                        ->where('group', $group)
                        ->update(['value' => $item['value']]);
                    $count++;
                }
            }

            Cache::forget('settings_all');

            return response()->json([
                'success' => true,
                'message' => "$count paramètres du groupe mis à jour",
                'data' => ['count' => $count],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du groupe',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer un paramètre.
     *
     * Endpoint : DELETE /api/v1/admin/settings/{key}
     */
    public function destroy($key)
    {
        try {
            $setting = Setting::where('key', $key)->first();

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paramètre introuvable',
                ], 404);
            }

            $setting->delete();

            Cache::forget('settings_all');
            Cache::forget('setting_' . $key);

            return response()->json([
                'success' => true,
                'message' => 'Paramètre supprimé avec succès',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du paramètre',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activer un paramètre.
     *
     * Endpoint : POST /api/v1/admin/settings/{key}/activate
     */
    public function activate($key)
    {
        try {
            $setting = Setting::where('key', $key)->first();

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paramètre introuvable',
                ], 404);
            }

            $setting->is_active = true;
            $setting->save();

            Cache::forget('settings_all');

            return response()->json([
                'success' => true,
                'message' => 'Paramètre activé',
                'data' => $this->formatSetting($setting),
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
     * Désactiver un paramètre.
     *
     * Endpoint : POST /api/v1/admin/settings/{key}/deactivate
     */
    public function deactivate($key)
    {
        try {
            $setting = Setting::where('key', $key)->first();

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paramètre introuvable',
                ], 404);
            }

            $setting->is_active = false;
            $setting->save();

            Cache::forget('settings_all');

            return response()->json([
                'success' => true,
                'message' => 'Paramètre désactivé',
                'data' => $this->formatSetting($setting),
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
     * Vérifier si une clé existe.
     *
     * Endpoint : GET /api/v1/admin/settings/check-key/{key}
     */
    public function checkKey($key)
    {
        try {
            $exists = Setting::where('key', $key)->exists();

            return response()->json([
                'success' => true,
                'message' => 'Vérification de la clé',
                'data' => ['exists' => $exists, 'available' => !$exists],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification de la clé',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les statistiques des paramètres.
     *
     * Endpoint : GET /api/v1/admin/settings/stats/count
     */
    public function statsCount()
    {
        try {
            $total = Setting::count();
            $public = Setting::where('is_public', true)->count();
            $private = Setting::where('is_public', false)->count();
            $active = Setting::where('is_active', true)->count();

            return response()->json([
                'success' => true,
                'message' => 'Statistiques récupérées',
                'data' => [
                    'total' => (int) $total,
                    'public' => (int) $public,
                    'private' => (int) $private,
                    'active' => (int) $active,
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
     * Récupérer le nombre de paramètres par groupe.
     *
     * Endpoint : GET /api/v1/admin/settings/stats/by-group
     */
    public function statsByGroup()
    {
        try {
            $groups = Setting::select('group', DB::raw('COUNT(*) as count'))
                ->groupBy('group')
                ->pluck('count', 'group')
                ->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Paramètres par groupe récupérés',
                'data' => $groups,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des paramètres par groupe',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Rafraîchir le cache des paramètres.
     *
     * Endpoint : POST /api/v1/admin/settings/cache/refresh
     */
    public function refreshCache()
    {
        try {
            Cache::forget('settings_all');

            $settings = Setting::where('is_active', true)->get();
            Cache::put('settings_all', $settings, now()->addHours(1));

            return response()->json([
                'success' => true,
                'message' => 'Cache rafraîchi avec succès',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rafraîchissement du cache',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Effacer le cache des paramètres.
     *
     * Endpoint : POST /api/v1/admin/settings/cache/clear
     */
    public function clearCache()
    {
        try {
            Cache::forget('settings_all');

            return response()->json([
                'success' => true,
                'message' => 'Cache effacé avec succès',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'effacement du cache',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Formater un paramètre pour la réponse API.
     */
    private function formatSetting(Setting $setting): array
    {
        return [
            'id' => $setting->id,
            'key' => $setting->key,
            'value' => $setting->value,
            'group' => $setting->group,
            'type' => $setting->type,
            'description' => $setting->description,
            'is_public' => (bool) $setting->is_public,
            'is_active' => (bool) $setting->is_active,
            'created_at' => $setting->created_at ? $setting->created_at->toIso8601String() : null,
            'updated_at' => $setting->updated_at ? $setting->updated_at->toIso8601String() : null,
        ];
    }
}