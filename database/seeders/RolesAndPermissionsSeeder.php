<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User\Role;
use App\Models\User\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Seed des rôles et permissions
     */
    public function run(): void
    {
        // 📊 Créer les permissions de base
        $permissions = [
            // Produits
            ['name' => 'create-product', 'display_name' => 'Créer un produit', 'group' => 'products', 'action' => 'create'],
            ['name' => 'read-product', 'display_name' => 'Lire un produit', 'group' => 'products', 'action' => 'read'],
            ['name' => 'update-product', 'display_name' => 'Modifier un produit', 'group' => 'products', 'action' => 'update'],
            ['name' => 'delete-product', 'display_name' => 'Supprimer un produit', 'group' => 'products', 'action' => 'delete'],
            
            // Commandes
            ['name' => 'create-order', 'display_name' => 'Créer une commande', 'group' => 'orders', 'action' => 'create'],
            ['name' => 'read-order', 'display_name' => 'Lire une commande', 'group' => 'orders', 'action' => 'read'],
            ['name' => 'update-order', 'display_name' => 'Modifier une commande', 'group' => 'orders', 'action' => 'update'],
            ['name' => 'delete-order', 'display_name' => 'Supprimer une commande', 'group' => 'orders', 'action' => 'delete'],
            
            // Utilisateurs
            ['name' => 'create-user', 'display_name' => 'Créer un utilisateur', 'group' => 'users', 'action' => 'create'],
            ['name' => 'read-user', 'display_name' => 'Lire un utilisateur', 'group' => 'users', 'action' => 'read'],
            ['name' => 'update-user', 'display_name' => 'Modifier un utilisateur', 'group' => 'users', 'action' => 'update'],
            ['name' => 'delete-user', 'display_name' => 'Supprimer un utilisateur', 'group' => 'users', 'action' => 'delete'],
            
            // Catégories
            ['name' => 'create-category', 'display_name' => 'Créer une catégorie', 'group' => 'categories', 'action' => 'create'],
            ['name' => 'read-category', 'display_name' => 'Lire une catégorie', 'group' => 'categories', 'action' => 'read'],
            ['name' => 'update-category', 'display_name' => 'Modifier une catégorie', 'group' => 'categories', 'action' => 'update'],
            ['name' => 'delete-category', 'display_name' => 'Supprimer une catégorie', 'group' => 'categories', 'action' => 'delete'],
            
            // Paiements
            ['name' => 'create-payment', 'display_name' => 'Créer un paiement', 'group' => 'payments', 'action' => 'create'],
            ['name' => 'read-payment', 'display_name' => 'Lire un paiement', 'group' => 'payments', 'action' => 'read'],
            ['name' => 'update-payment', 'display_name' => 'Modifier un paiement', 'group' => 'payments', 'action' => 'update'],
            
            // Avis
            ['name' => 'moderate-review', 'display_name' => 'Modérer un avis', 'group' => 'reviews', 'action' => 'manage'],
            
            // Paramètres
            ['name' => 'manage-settings', 'display_name' => 'Gérer les paramètres', 'group' => 'settings', 'action' => 'manage'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        // 📊 Créer les rôles
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Administrateur',
                'description' => 'Accès complet à toutes les fonctionnalités',
                'level' => 100,
                'is_system' => true,
            ],
            [
                'name' => 'seller',
                'display_name' => 'Vendeur',
                'description' => 'Gère ses propres produits et commandes',
                'level' => 50,
                'is_system' => false,
            ],
            [
                'name' => 'client',
                'display_name' => 'Client',
                'description' => 'Achète des produits',
                'level' => 10,
                'is_system' => true,
            ],
        ];

        foreach ($roles as $roleData) {
            $role = Role::updateOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );

            // Attribuer les permissions selon le rôle
            if ($role->name === 'admin') {
                $role->givePermissionTo(Permission::all()->pluck('name')->toArray());
            } elseif ($role->name === 'seller') {
                $role->givePermissionTo([
                    'create-product', 'read-product', 'update-product',
                    'create-order', 'read-order', 'update-order',
                    'read-user',
                    'create-payment', 'read-payment',
                ]);
            } elseif ($role->name === 'client') {
                $role->givePermissionTo([
                    'read-product',
                    'create-order', 'read-order',
                    'create-payment', 'read-payment',
                ]);
            }
        }
    }
}