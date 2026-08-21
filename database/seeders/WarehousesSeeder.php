<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Catalog\Warehouse;

class WarehousesSeeder extends Seeder
{
    /**
     * Seed des entrepôts
     */
    public function run(): void
    {
        $warehouses = [
            [
                'name' => 'Entrepôt Principal Douala',
                'code' => 'WH-DLA-001',
                'description' => 'Entrepôt central de Douala',
                'address_line1' => 'Zone Industrielle Bonaberi',
                'address_line2' => 'Rue 1234',
                'city' => 'Douala',
                'state' => 'Littoral',
                'country' => 'Cameroun',
                'latitude' => 4.0511,
                'longitude' => 9.7679,
                'phone' => '+237 6 50 00 00 01',
                'email' => 'entrepot.douala@shoppro.com',
                'manager_name' => 'Jean Kamga',
                'capacity' => 10000,
                'current_occupancy' => 0,
                'unit' => 'm2',
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Entrepôt Yaoundé',
                'code' => 'WH-YDE-001',
                'description' => 'Entrepôt secondaire de Yaoundé',
                'address_line1' => 'Zone Industrielle Mvan',
                'address_line2' => 'Rue 5678',
                'city' => 'Yaoundé',
                'state' => 'Centre',
                'country' => 'Cameroun',
                'latitude' => 3.8480,
                'longitude' => 11.5021,
                'phone' => '+237 6 50 00 00 02',
                'email' => 'entrepot.yaounde@shoppro.com',
                'manager_name' => 'Marie Ngo',
                'capacity' => 5000,
                'current_occupancy' => 0,
                'unit' => 'm2',
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Entrepôt Dakar',
                'code' => 'WH-DKR-001',
                'description' => 'Entrepôt régional de Dakar',
                'address_line1' => 'Zone Industrielle Rufisque',
                'city' => 'Dakar',
                'country' => 'Sénégal',
                'latitude' => 14.7167,
                'longitude' => -17.4677,
                'phone' => '+221 77 000 00 01',
                'email' => 'entrepot.dakar@shoppro.com',
                'manager_name' => 'Abdou Diop',
                'capacity' => 8000,
                'current_occupancy' => 0,
                'unit' => 'm2',
                'is_default' => false,
                'is_active' => true,
            ],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::updateOrCreate(
                ['code' => $warehouse['code']],
                $warehouse
            );
        }
    }
}