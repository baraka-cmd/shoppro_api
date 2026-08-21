<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Config\Language;

class LanguagesSeeder extends Seeder
{
    /**
     * Seed des langues
     */
    public function run(): void
    {
        $languages = [
            [
                'name' => 'French',
                'code' => 'fr',
                'native_name' => 'Français',
                'flag_emoji' => '🇫🇷',
                'direction' => 'ltr',
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'name' => 'English',
                'code' => 'en',
                'native_name' => 'English',
                'flag_emoji' => '🇬🇧',
                'direction' => 'ltr',
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Arabic',
                'code' => 'ar',
                'native_name' => 'العربية',
                'flag_emoji' => '🇸🇦',
                'direction' => 'rtl',
                'is_default' => false,
                'is_active' => true,
            ],
        ];

        foreach ($languages as $language) {
            Language::updateOrCreate(
                ['code' => $language['code']],
                $language
            );
        }
    }
}