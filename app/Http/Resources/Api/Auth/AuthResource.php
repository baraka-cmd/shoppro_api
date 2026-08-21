<?php

namespace App\Http\Resources\Api\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    /**
     * Transformer la ressource en tableau
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => new UserResource($this['user']),
            'token' => $this['token'] ?? null,
            'token_type' => 'Bearer',
            'expires_at' => $this['expires_at'] ?? null,
        ];
    }

    /**
     * Ajouter des métadonnées à la réponse
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'success' => true,
            'message' => $this['message'] ?? 'Opération réussie',
        ];
    }
}