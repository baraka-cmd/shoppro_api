<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Phase 1 : Configuration de base
            RolesAndPermissionsSeeder::class,
            SettingsSeeder::class,
            CurrenciesSeeder::class,
            LanguagesSeeder::class,

            // Phase 2 : Données géographiques
            CountriesSeeder::class,
            CitiesSeeder::class,
            ShippingMethodsSeeder::class,
            PaymentMethodsSeeder::class,

            // Phase 3 : Données catalogue
            CategoriesSeeder::class,
            SubcategoriesSeeder::class,
            BrandsSeeder::class,
            TagsSeeder::class,

            // Phase 4 : Données produits
            ProductsSeeder::class,
            ProductImagesSeeder::class,
            ProductVariantsSeeder::class,
            ProductAttributesSeeder::class,

            // Phase 5 : Données stock
            WarehousesSeeder::class,
            StocksSeeder::class,
            SuppliersSeeder::class,
            StockMovementsSeeder::class,

            // Phase 6 : Données utilisateurs
            UsersSeeder::class,
            UserProfilesSeeder::class,
            UserAddressesSeeder::class,
            FavoritesSeeder::class,

            // Phase 7 : Données commandes
            CartsSeeder::class,
            CartItemsSeeder::class,
            OrdersSeeder::class,
            OrderItemsSeeder::class,

            // Phase 8 : Données interactions
            ReviewsSeeder::class,
            ReviewImagesSeeder::class,
            ReviewVotesSeeder::class,
            CouponsSeeder::class,

            // Phase 9 : Données marketing
            PromotionsSeeder::class,
            FlashSalesSeeder::class,
            BannersSeeder::class,
            NewsletterSubscribersSeeder::class,

            // Phase 10 : Données support
            NotificationsSeeder::class,
            SupportTicketsSeeder::class,
            TicketMessagesSeeder::class,
            FaqCategoriesSeeder::class,     // ✅ AJOUTÉ
            FaqsSeeder::class,               // ✅ AJOUTÉ
            LegalDocumentsSeeder::class,     // ✅ AJOUTÉ

            // Phase 11 : Données paiement
            PaymentsSeeder::class,
            TransactionsSeeder::class,
            RefundsSeeder::class,
            AdminLogsSeeder::class,
        ]);
    }
}