<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Config\Country;

class CountriesSeeder extends Seeder
{
    /**
     * Seed des pays
     */
    public function run(): void
    {
        $countries = [
            [
                'name' => 'Cameroun',
                'code' => 'CM',
                'iso3' => 'CMR',
                'phone_code' => '+237',
                'flag_emoji' => '🇨🇲',
                'currency_code' => 'FCFA',
                'currency_name' => 'Franc CFA',
                'currency_symbol' => 'FCFA',
                'language_code' => 'fr',
                'is_delivery_available' => true,
                'delivery_fee' => 2000,
                'delivery_days' => 5,
                'latitude' => 3.8480,
                'longitude' => 11.5021,
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Sénégal',
                'code' => 'SN',
                'iso3' => 'SEN',
                'phone_code' => '+221',
                'flag_emoji' => '🇸🇳',
                'currency_code' => 'FCFA',
                'currency_name' => 'Franc CFA',
                'currency_symbol' => 'FCFA',
                'language_code' => 'fr',
                'is_delivery_available' => true,
                'delivery_fee' => 5000,
                'delivery_days' => 7,
                'latitude' => 14.4974,
                'longitude' => -14.4524,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => "Côte d'Ivoire",
                'code' => 'CI',
                'iso3' => 'CIV',
                'phone_code' => '+225',
                'flag_emoji' => '🇨🇮',
                'currency_code' => 'FCFA',
                'currency_name' => 'Franc CFA',
                'currency_symbol' => 'FCFA',
                'language_code' => 'fr',
                'is_delivery_available' => true,
                'delivery_fee' => 5000,
                'delivery_days' => 7,
                'latitude' => 6.8276,
                'longitude' => -5.2893,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'France',
                'code' => 'FR',
                'iso3' => 'FRA',
                'phone_code' => '+33',
                'flag_emoji' => '🇫🇷',
                'currency_code' => 'EUR',
                'currency_name' => 'Euro',
                'currency_symbol' => '€',
                'language_code' => 'fr',
                'is_delivery_available' => true,
                'delivery_fee' => 15000,
                'delivery_days' => 10,
                'latitude' => 46.2276,
                'longitude' => 2.2137,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'États-Unis',
                'code' => 'US',
                'iso3' => 'USA',
                'phone_code' => '+1',
                'flag_emoji' => '🇺🇸',
                'currency_code' => 'USD',
                'currency_name' => 'Dollar US',
                'currency_symbol' => '$',
                'language_code' => 'en',
                'is_delivery_available' => true,
                'delivery_fee' => 25000,
                'delivery_days' => 14,
                'latitude' => 37.0902,
                'longitude' => -95.7129,
                'is_default' => false,
                'is_active' => true,
            ],
        ];

        foreach ($countries as $country) {
            Country::updateOrCreate(
                ['code' => $country['code']],
                $country
            );
        }
    }
}