<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Admin\AdminLog;

class AdminLogsSeeder extends Seeder
{
    /**
     * Seed des logs administrateurs
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@shoppro.com')->first();

        if (!$admin) {
            return;
        }

        $logs = [
            [
                'action' => 'create_product',
                'module' => 'products',
                'description' => 'Création de nouveaux produits',
                'is_successful' => true,
            ],
            [
                'action' => 'update_settings',
                'module' => 'settings',
                'description' => 'Mise à jour des paramètres généraux',
                'is_successful' => true,
            ],
            [
                'action' => 'delete_review',
                'module' => 'reviews',
                'description' => 'Suppression d\'un avis inapproprié',
                'is_successful' => true,
            ],
            [
                'action' => 'update_order',
                'module' => 'orders',
                'description' => 'Modification du statut d\'une commande',
                'is_successful' => true,
            ],
            [
                'action' => 'create_category',
                'module' => 'categories',
                'description' => 'Création d\'une nouvelle catégorie',
                'is_successful' => true,
            ],
            [
                'action' => 'update_product',
                'module' => 'products',
                'description' => 'Mise à jour d\'un produit',
                'is_successful' => false,
                'error_message' => 'Erreur de validation des données',
            ],
        ];

        foreach ($logs as $index => $logData) {
            AdminLog::create([
                'user_id' => $admin->id,
                'action' => $logData['action'],
                'module' => $logData['module'],
                'description' => $logData['description'],
                'target_type' => $logData['module'],
                'target_id' => rand(1, 50),
                'old_data' => ['name' => 'Ancienne valeur'],
                'new_data' => ['name' => 'Nouvelle valeur'],
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Seeder)',
                'url' => '/admin/' . $logData['module'],
                'method' => 'POST',
                'is_successful' => $logData['is_successful'],
                'error_message' => $logData['error_message'] ?? null,
            ]);
        }
    }
}