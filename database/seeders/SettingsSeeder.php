<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin\Setting;

class SettingsSeeder extends Seeder
{
    /**
     * Seed des paramètres généraux
     */
    public function run(): void
    {
        $settings = [
            // Général
            ['key' => 'site_name', 'value' => 'ShopPro', 'group' => 'general', 'type' => 'string', 'is_public' => true],
            ['key' => 'site_description', 'value' => 'Application e-commerce professionnelle', 'group' => 'general', 'type' => 'string', 'is_public' => true],
            ['key' => 'site_logo', 'value' => null, 'group' => 'general', 'type' => 'file', 'is_public' => true],
            ['key' => 'contact_email', 'value' => 'contact@shoppro.com', 'group' => 'general', 'type' => 'string', 'is_public' => true],
            ['key' => 'contact_phone', 'value' => '+237 6 00 00 00 00', 'group' => 'general', 'type' => 'string', 'is_public' => true],
            
            // Livraison
            ['key' => 'free_shipping_threshold', 'value' => '50000', 'group' => 'shipping', 'type' => 'float', 'is_public' => true],
            ['key' => 'default_shipping_fee', 'value' => '2000', 'group' => 'shipping', 'type' => 'float', 'is_public' => true],
            ['key' => 'delivery_days', 'value' => '7', 'group' => 'shipping', 'type' => 'integer', 'is_public' => true],
            
            // Paiement
            ['key' => 'default_currency', 'value' => 'FCFA', 'group' => 'payment', 'type' => 'string', 'is_public' => true],
            ['key' => 'tax_rate', 'value' => '19.25', 'group' => 'payment', 'type' => 'float', 'is_public' => true],
            ['key' => 'cash_on_delivery', 'value' => 'true', 'group' => 'payment', 'type' => 'boolean', 'is_public' => true],
            
            // SEO
            ['key' => 'meta_title', 'value' => 'ShopPro - E-commerce', 'group' => 'seo', 'type' => 'string', 'is_public' => true],
            ['key' => 'meta_description', 'value' => 'Achetez en ligne les meilleurs produits', 'group' => 'seo', 'type' => 'string', 'is_public' => true],
            
            // Réseaux sociaux
            ['key' => 'facebook_url', 'value' => 'https://facebook.com/shoppro', 'group' => 'social', 'type' => 'string', 'is_public' => true],
            ['key' => 'instagram_url', 'value' => 'https://instagram.com/shoppro', 'group' => 'social', 'type' => 'string', 'is_public' => true],
            ['key' => 'whatsapp_number', 'value' => '+237600000000', 'group' => 'social', 'type' => 'string', 'is_public' => true],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}