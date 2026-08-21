<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order\Supplier;

class SuppliersSeeder extends Seeder
{
    /**
     * Seed des fournisseurs
     */
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'TechDistrib SARL',
                'code' => 'SUP-TECH-001',
                'description' => 'Distributeur officiel de produits électroniques',
                'contact_name' => 'Paul Essomba',
                'email' => 'contact@techdistrib.com',
                'phone' => '+237 6 60 00 00 01',
                'phone_alt' => '+237 6 60 00 00 02',
                'address_line1' => 'Akwa, Rue des Fleurs',
                'city' => 'Douala',
                'country' => 'Cameroun',
                'bank_name' => 'Afriland First Bank',
                'account_number' => 'CM2110001000100010001',
                'account_name' => 'TechDistrib SARL',
                'payment_terms' => 30,
                'delivery_time' => 7,
                'minimum_order' => 100000,
                'is_preferred' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Mode Import Export',
                'code' => 'SUP-MODE-001',
                'description' => 'Importateur de vêtements et accessoires',
                'contact_name' => 'Sophie Mbarga',
                'email' => 'contact@modeimport.com',
                'phone' => '+237 6 60 00 00 03',
                'address_line1' => 'Bonamoussadi, Rue 123',
                'city' => 'Douala',
                'country' => 'Cameroun',
                'bank_name' => 'Ecobank',
                'account_number' => 'CM2110002000200020002',
                'account_name' => 'Mode Import Export',
                'payment_terms' => 15,
                'delivery_time' => 14,
                'minimum_order' => 50000,
                'is_preferred' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Beauté Plus Distribution',
                'code' => 'SUP-BEAU-001',
                'description' => 'Distributeur de produits cosmétiques',
                'contact_name' => 'Claire Njoya',
                'email' => 'contact@beauteplus.com',
                'phone' => '+237 6 60 00 00 04',
                'address_line1' => 'Bastos, Rue 456',
                'city' => 'Yaoundé',
                'country' => 'Cameroun',
                'bank_name' => 'BICEC',
                'account_number' => 'CM2110003000300030003',
                'account_name' => 'Beauté Plus Distribution',
                'payment_terms' => 30,
                'delivery_time' => 5,
                'minimum_order' => 25000,
                'is_preferred' => true,
                'is_active' => true,
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::updateOrCreate(
                ['code' => $supplier['code']],
                $supplier
            );
        }
    }
}