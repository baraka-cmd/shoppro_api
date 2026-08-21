<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Vérifier le rôle de l'utilisateur
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié',
                'error' => 'unauthenticated'
            ], 401);
        }

        // Vérifier si l'utilisateur a le rôle requis
        if (!$request->user()->hasRole($roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
                'error' => 'forbidden'
            ], 403);
        }

        return $next($request);
    }
}