<?php

namespace App\Http\Controllers\Api\Address;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Address\StoreAddressRequest;
use App\Http\Requests\Api\Address\UpdateAddressRequest;
use App\Http\Resources\Api\Address\UserAddressResource;
use App\Models\User\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    // ============================================
    // LISTE DES ADRESSES DE L'UTILISATEUR
    // ============================================
    /**
     * Récupérer la liste des adresses de l'utilisateur connecté.
     *
     * Endpoint : GET /api/v1/addresses
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            $query = UserAddress::where('user_id', $user->id)
                ->where('is_active', true);

            // Filtre par type (home, work, other)
            if ($request->filled('type')) {
                $query->where('type', $request->query('type'));
            }

            // Filtre : seulement les adresses par défaut
            if ($request->filled('default') && $request->query('default') === '1') {
                $query->where('is_default', true);
            }

            // Tri : adresses par défaut en premier, puis par date de création
            $addresses = $query
                ->orderByDesc('is_default')
                ->orderByDesc('created_at')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Adresses récupérées avec succès.',
                'data' => UserAddressResource::collection($addresses),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des adresses.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // DÉTAILS D'UNE ADRESSE
    // ============================================
    /**
     * Récupérer les détails d'une adresse.
     *
     * Endpoint : GET /api/v1/addresses/{id}
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $user = Auth::user();

            $address = UserAddress::where('user_id', $user->id)
                ->where('is_active', true)
                ->find($id);

            if (!$address) {
                return response()->json([
                    'success' => false,
                    'message' => 'Adresse introuvable.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Adresse récupérée avec succès.',
                'data' => new UserAddressResource($address),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'adresse.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // CRÉATION D'UNE ADRESSE
    // ============================================
    /**
     * Créer une nouvelle adresse.
     *
     * Endpoint : POST /api/v1/addresses
     *
     * @param StoreAddressRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreAddressRequest $request)
    {
        try {
            $user = Auth::user();

            DB::beginTransaction();

            // Si c'est la première adresse → elle devient par défaut
            $addressCount = UserAddress::where('user_id', $user->id)
                ->where('is_active', true)
                ->count();

            $isDefault = $request->boolean('is_default', false);

            if ($addressCount === 0) {
                $isDefault = true;
            }

            // Si la nouvelle adresse est par défaut → retirer le défaut des autres
            if ($isDefault) {
                UserAddress::where('user_id', $user->id)
                    ->update(['is_default' => false]);
            }

            // Créer l'adresse
            $address = UserAddress::create([
                'user_id' => $user->id,
                'full_name' => $request->full_name,
                'phone' => $request->phone,
                'email' => $request->email,
                'address_line1' => $request->address_line1,
                'address_line2' => $request->address_line2,
                'city' => $request->city,
                'state' => $request->state,
                'postal_code' => $request->postal_code,
                'country' => $request->country ?? 'Cameroun',
                'type' => $request->type ?? 'home',
                'is_default' => $isDefault,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'delivery_instructions' => $request->delivery_instructions,
                'is_active' => true,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Adresse créée avec succès.',
                'data' => new UserAddressResource($address),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'adresse.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // MODIFICATION D'UNE ADRESSE
    // ============================================
    /**
     * Mettre à jour une adresse existante.
     *
     * Endpoint : PUT /api/v1/addresses/{id}
     *
     * @param UpdateAddressRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateAddressRequest $request, $id)
    {
        try {
            $user = Auth::user();

            $address = UserAddress::where('user_id', $user->id)
                ->where('is_active', true)
                ->find($id);

            if (!$address) {
                return response()->json([
                    'success' => false,
                    'message' => 'Adresse introuvable.',
                ], 404);
            }

            DB::beginTransaction();

            // Si on demande à rendre cette adresse par défaut
            if ($request->has('is_default') && $request->boolean('is_default')) {
                UserAddress::where('user_id', $user->id)
                    ->where('id', '!=', $address->id)
                    ->update(['is_default' => false]);
            }

            // Mettre à jour les champs
            $address->update([
                'full_name' => $request->input('full_name', $address->full_name),
                'phone' => $request->input('phone', $address->phone),
                'email' => $request->input('email', $address->email),
                'address_line1' => $request->input('address_line1', $address->address_line1),
                'address_line2' => $request->input('address_line2', $address->address_line2),
                'city' => $request->input('city', $address->city),
                'state' => $request->input('state', $address->state),
                'postal_code' => $request->input('postal_code', $address->postal_code),
                'country' => $request->input('country', $address->country),
                'type' => $request->input('type', $address->type),
                'is_default' => $request->has('is_default')
                    ? $request->boolean('is_default')
                    : $address->is_default,
                'latitude' => $request->input('latitude', $address->latitude),
                'longitude' => $request->input('longitude', $address->longitude),
                'delivery_instructions' => $request->input(
                    'delivery_instructions',
                    $address->delivery_instructions
                ),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Adresse mise à jour avec succès.',
                'data' => new UserAddressResource($address->fresh()),
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'adresse.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // SUPPRESSION D'UNE ADRESSE (SOFT DELETE)
    // ============================================
    /**
     * Supprimer une adresse (désactivation).
     *
     * Endpoint : DELETE /api/v1/addresses/{id}
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();

            $address = UserAddress::where('user_id', $user->id)
                ->where('is_active', true)
                ->find($id);

            if (!$address) {
                return response()->json([
                    'success' => false,
                    'message' => 'Adresse introuvable.',
                ], 404);
            }

            DB::beginTransaction();

            $wasDefault = $address->is_default;

            // Soft delete
            $address->update(['is_active' => false]);
            $address->delete();

            // Si c'était l'adresse par défaut → en désigner une autre
            if ($wasDefault) {
                $newDefault = UserAddress::where('user_id', $user->id)
                    ->where('is_active', true)
                    ->orderByDesc('created_at')
                    ->first();

                if ($newDefault) {
                    $newDefault->update(['is_default' => true]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Adresse supprimée avec succès.',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'adresse.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // DÉFINIR UNE ADRESSE PAR DÉFAUT
    // ============================================
    /**
     * Définir une adresse comme adresse par défaut.
     *
     * Endpoint : POST /api/v1/addresses/{id}/set-default
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function setDefault($id)
    {
        try {
            $user = Auth::user();

            $address = UserAddress::where('user_id', $user->id)
                ->where('is_active', true)
                ->find($id);

            if (!$address) {
                return response()->json([
                    'success' => false,
                    'message' => 'Adresse introuvable.',
                ], 404);
            }

            DB::beginTransaction();

            // Retirer le défaut des autres adresses
            UserAddress::where('user_id', $user->id)
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);

            // Définir cette adresse comme par défaut
            $address->update(['is_default' => true]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Adresse par défaut mise à jour.',
                'data' => new UserAddressResource($address->fresh()),
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'adresse par défaut.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}