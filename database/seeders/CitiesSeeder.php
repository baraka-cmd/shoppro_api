<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Config\City;
use App\Models\Config\Country;

class CitiesSeeder extends Seeder
{
    /**
     * Seed des villes
     */
    public function run(): void
    {
        $cities = [
            // Cameroun
            ['country_code' => 'CM', 'name' => 'Douala', 'code' => 'DLA', 'postal_code' => 'BP 0000', 'delivery_fee' => 1500, 'delivery_days' => 2, 'is_popular' => true],
            ['country_code' => 'CM', 'name' => 'Yaoundé', 'code' => 'YDE', 'postal_code' => 'BP 0000', 'delivery_fee' => 1500, 'delivery_days' => 2, 'is_popular' => true],
            ['country_code' => 'CM', 'name' => 'Bafoussam', 'code' => 'BFM', 'postal_code' => 'BP 0000', 'delivery_fee' => 3000, 'delivery_days' => 3, 'is_popular' => false],
            ['country_code' => 'CM', 'name' => 'Garoua', 'code' => 'GRU', 'postal_code' => 'BP 0000', 'delivery_fee' => 4000, 'delivery_days' => 4, 'is_popular' => false],
            
            // Sénégal
            ['country_code' => 'SN', 'name' => 'Dakar', 'code' => 'DKR', 'postal_code' => 'BP 0000', 'delivery_fee' => 2000, 'delivery_days' => 2, 'is_popular' => true],
            ['country_code' => 'SN', 'name' => 'Thiès', 'code' => 'THS', 'postal_code' => 'BP 0000', 'delivery_fee' => 3500, 'delivery_days' => 3, 'is_popular' => false],
            
            // Côte d'Ivoire
            ['country_code' => 'CI', 'name' => 'Abidjan', 'code' => 'ABJ', 'postal_code' => 'BP 0000', 'delivery_fee' => 2000, 'delivery_days' => 2, 'is_popular' => true],
            ['country_code' => 'CI', 'name' => 'Bouaké', 'code' => 'BKE', 'postal_code' => 'BP 0000', 'delivery_fee' => 4000, 'delivery_days' => 3, 'is_popular' => false],
            
            // France
            ['country_code' => 'FR', 'name' => 'Paris', 'code' => 'PAR', 'postal_code' => '75000', 'delivery_fee' => 5000, 'delivery_days' => 2, 'is_popular' => true],
            ['country_code' => 'FR', 'name' => 'Lyon', 'code' => 'LYO', 'postal_code' => '69000', 'delivery_fee' => 5000, 'delivery_days' => 2, 'is_popular' => false],
            
            // États-Unis
            ['country_code' => 'US', 'name' => 'New York', 'code' => 'NYC', 'postal_code' => '10001', 'delivery_fee' => 8000, 'delivery_days' => 3, 'is_popular' => true],
            ['country_code' => 'US', 'name' => 'Los Angeles', 'code' => 'LAX', 'postal_code' => '90001', 'delivery_fee' => 8000, 'delivery_days' => 4, 'is_popular' => false],
        ];

        foreach ($cities as $cityData) {
            $country = Country::where('code', $cityData['country_code'])->first();
            
            if ($country) {
                City::updateOrCreate(
                    ['country_id' => $country->id, 'name' => $cityData['name']],
                    [
                        'country_id' => $country->id,
                        'name' => $cityData['name'],
                        'code' => $cityData['code'],
                        'postal_code' => $cityData['postal_code'],
                        'is_delivery_available' => true,
                        'delivery_fee' => $cityData['delivery_fee'],
                        'delivery_days' => $cityData['delivery_days'],
                        'is_popular' => $cityData['is_popular'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}