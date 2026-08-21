<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Payment\PaymentMethod;

class PaymentMethodsSeeder extends Seeder
{
    /**
     * Seed des méthodes de paiement
     */
    public function run(): void
    {
        $paymentMethods = [
            [
                'name' => 'Paiement à la livraison',
                'code' => 'cash_on_delivery',
                'description' => 'Payez en espèces à la réception de votre commande',
                'icon' => 'cash',
                'transaction_fee' => 0,
                'fixed_fee' => 0,
                'minimum_amount' => 1000,
                'maximum_amount' => 500000,
                'currencies' => ['FCFA'],
                'position' => 1,
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Mobile Money',
                'code' => 'mobile_money',
                'description' => 'Payez avec MTN Mobile Money ou Orange Money',
                'icon' => 'phone',
                'transaction_fee' => 1.5,
                'fixed_fee' => 50,
                'minimum_amount' => 500,
                'maximum_amount' => 1000000,
                'currencies' => ['FCFA'],
                'position' => 2,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Carte bancaire',
                'code' => 'card',
                'description' => 'Payez avec votre carte Visa ou Mastercard',
                'icon' => 'card',
                'transaction_fee' => 2.5,
                'fixed_fee' => 100,
                'minimum_amount' => 1000,
                'maximum_amount' => 5000000,
                'currencies' => ['FCFA', 'USD', 'EUR'],
                'position' => 3,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Virement bancaire',
                'code' => 'bank_transfer',
                'description' => 'Effectuez un virement bancaire',
                'icon' => 'bank',
                'transaction_fee' => 0,
                'fixed_fee' => 0,
                'minimum_amount' => 5000,
                'maximum_amount' => 10000000,
                'currencies' => ['FCFA', 'USD', 'EUR'],
                'position' => 4,
                'is_default' => false,
                'is_active' => true,
            ],
        ];

        foreach ($paymentMethods as $method) {
            PaymentMethod::updateOrCreate(
                ['code' => $method['code']],
                $method
            );
        }
    }
}