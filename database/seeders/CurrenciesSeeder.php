<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin\Currency;

class CurrenciesSeeder extends Seeder
{
    /**
     * Seed des devises
     */
    public function run(): void
    {
        $currencies = [
            [
                'name' => 'Franc CFA',
                'code' => 'FCFA',
                'symbol' => 'FCFA',
                'exchange_rate' => 1,
                'decimal_separator' => '.',
                'thousand_separator' => ',',
                'decimal_places' => 0,
                'symbol_position' => 'after',
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Dollar US',
                'code' => 'USD',
                'symbol' => '$',
                'exchange_rate' => 0.0016,
                'decimal_separator' => '.',
                'thousand_separator' => ',',
                'decimal_places' => 2,
                'symbol_position' => 'before',
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Euro',
                'code' => 'EUR',
                'symbol' => '€',
                'exchange_rate' => 0.0015,
                'decimal_separator' => ',',
                'thousand_separator' => '.',
                'decimal_places' => 2,
                'symbol_position' => 'before',
                'is_default' => false,
                'is_active' => true,
            ],
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }
    }
}