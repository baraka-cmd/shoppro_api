Parfait 🎯 — voici maintenant le document 2 pour le côté Laravel/API.

---

📄 docs/10-fichiers-cles-api.md

⚠️ À CRÉER — Chemin : C:\Users\barak\docs\10-fichiers-cles-api.md

```markdown
# 🖥️ FICHIERS CLÉS - SHOPPRO API (LARAVEL)

> **Document de continuité** — À lire AVANT toute modification côté API.
> Ce fichier suit `08-prochaines-etapes.md` (Laravel) et complète les docs 00 à 08.
> **Dernière mise à jour : 03/10/2026**

---

## 🎯 RÉSUMÉ EXÉCUTIF POUR UN NOUVEAU CHAT

Tu vas reprendre le développement du **backend Laravel** de ShopPro.
Voici les informations ESSENTIELLES pour continuer sans rien casser :

- **État** : ~85% terminé
- **Framework** : Laravel 13.x
- **PHP** : 8.5
- **Base de données** : MySQL (base `shoppro_db`)
- **Auth** : Laravel Sanctum (tokens)
- **PDF** : `barryvdh/laravel-dompdf` (installé)
- **URL** : `http://127.0.0.1:8000/api/v1`
- **Commande** : `php artisan serve`

⚠️ **4 contrôleurs viennent d'être refaits** : Commandes, Adresses, Avis, Bannières.

---

## 📊 ÉTAT D'AVANCEMENT

| Module | Statut | Notes |
|--------|--------|-------|
| Base de données | ✅ 100% | 60+ migrations, 47 seeders |
| Authentification | ✅ 100% | Sanctum + RefreshToken |
| Produits | ✅ 100% | CRUD + filtres + recherche |
| Catégories | ✅ 100% | CRUD |
| Marques | ✅ 100% | CRUD |
| Bannières | ✅ 100% | CRUD |
| **Commandes** | ✅ 100% | **Refait avec items directs** |
| **Adresses** | ✅ 100% | **CRÉÉ de zéro** |
| **Avis** | ✅ 100% | **Refait avec photos** |
| Admin (Users, Settings, Notifications, Stocks, Promotions, Reviews) | ✅ 100% | Complet |
| Dashboard (stats, revenus) | ✅ 100% | Complet |
| Support (FAQ, tickets) | ✅ 100% | Complet |
| Legal (documents) | ✅ 100% | Complet |
| Paiement | ⚠️ 40% | Simulation uniquement |
| Notifications push (FCM) | ❌ 0% | À faire |
| Retours commandes | ⚠️ 50% | Backend partiel |

**Total : ~85%**

---

## 🔥 CE QUI VIENT D'ÊTRE FAIT (03/10/2026)

### 1. Adresses — Contrôleur CRÉÉ de zéro ✅

**Avant** : aucun `AddressController` — l'app Flutter gérait tout en local.

**Fichiers créés** :
- `app/Http/Controllers/Api/Address/AddressController.php` ✅ **CRÉÉ**
- `app/Http/Requests/Api/Address/StoreAddressRequest.php` ✅ **CRÉÉ**
- `app/Http/Requests/Api/Address/UpdateAddressRequest.php` ✅ **CRÉÉ**
- `app/Http/Resources/Api/Address/UserAddressResource.php` ✅ **CRÉÉ**

**Routes ajoutées** (dans `routes/api.php`, section protégée `v1` + `auth:sanctum`) :
```php
Route::prefix('addresses')->group(function () {
    Route::get('/', [AddressController::class, 'index']);
    Route::post('/', [AddressController::class, 'store']);
    Route::get('/{id}', [AddressController::class, 'show']);
    Route::put('/{id}', [AddressController::class, 'update']);
    Route::delete('/{id}', [AddressController::class, 'destroy']);
    Route::post('/{id}/set-default', [AddressController::class, 'setDefault']);
});
```

Import en haut de routes/api.php :

```php
use App\Http\Controllers\Api\Address\AddressController;
```

Fonctionnalités :

· Première adresse → automatiquement par défaut
· Définir une adresse par défaut → retire le défaut des autres
· Soft delete (is_active = false + deleted_at)
· Si on supprime la défaut → une autre prend le relais

2. Commandes — store() REFONDU ✅

Avant : store() utilisait le panier côté serveur (Cart::where(...)) — mais l'app ne synchronisait JAMAIS son panier.

Résultat : erreur "Votre panier est vide".

Après : store() accepte les items directement dans la requête.

Fichier refait :

· app/Http/Controllers/Api/Order/OrderController.php ✅ REFAIT

Changements :

· ❌ Retiré : use App\Models\Order\Cart; et CartItem;
· ✅ Ajouté : use App\Models\Catalog\ProductVariant;
· Le body attend maintenant :
  ```json
  {
    "shipping_address_id": 1,
    "billing_address_id": 1,
    "shipping_method": "standard",
    "payment_method": "cash_on_delivery",
    "coupon_code": null,
    "customer_note": null,
    "items": [
      { "product_id": 1, "quantity": 2, "variant_id": null },
      { "product_id": 3, "quantity": 1, "variant_id": null }
    ]
  }
  ```
· Le serveur calcule tous les totaux (sécurité)
· Le serveur vérifie le stock et le décrémente
· Le serveur génère le numéro (CMD-YYYYMMDD-XXXXXX)
· Retourne la commande complète (avec items, shippingAddress, payments)

Nouvelle méthode : invoiceByNumber($orderNumber)

· Endpoint : GET /orders/by-number/{orderNumber}/invoice
· Cherche par order_number (plus fiable que par id côté app)

Méthode privée : calculateShippingFee($method, $subtotal)

· Standard : 2000 FCFA (gratuit si > 100 000)
· Express : 5000 FCFA
· Pickup : 1000 FCFA

3. Avis avec photos — REFONDU ✅

Fichiers refaits :

· app/Http/Controllers/Api/Review/ReviewController.php ✅ REFAIT
· app/Http/Requests/Api/Review/StoreReviewRequest.php ✅ CRÉÉ
· app/Http/Resources/Api/Review/ReviewResource.php ✅ CRÉÉ

Routes :

```php
// Public
Route::get('/products/{productId}/reviews', [ReviewController::class, 'index']);

// Protégé
Route::prefix('reviews')->group(function () {
    Route::post('/', [ReviewController::class, 'store']);
    Route::put('/{id}', [ReviewController::class, 'update']);
    Route::delete('/{id}', [ReviewController::class, 'destroy']);
    Route::post('/{id}/vote', [ReviewController::class, 'vote']);
});
```

Fonctionnalités :

· Upload de photos (max 5, formats jpeg/jpg/png/gif/webp, max 5 Mo)
· Stockage dans storage/app/public/reviews/
· Vote utile/pas utile (toggle)
· is_verified_purchase (si l'utilisateur a acheté)
· Statistiques : average_rating, total_reviews, rating_distribution
· Filtres : par note, avec photos, vérifiés
· Tri : récents, utiles, notes
· Met à jour products.average_rating et products.reviews_count

4. PDF Facture — NOUVEAU ✅

Installation :

```cmd
composer require barryvdh/laravel-dompdf
```

Fichiers créés :

· resources/views/invoices/order.blade.php ✅ CRÉÉ

Template Blade contient :

· En-tête ShopPro (logo, coordonnées)
· Infos client (nom, email, téléphone)
· Adresse de livraison
· N° de commande, date, statut
· Table des articles (SKU, variantes, quantités, prix)
· Totaux (sous-total, remise, livraison, taxes, total)
· Note client
· Pied de page

Endpoints :

· GET /admin/orders/{id}/invoice → pour l'admin
· GET /orders/{id}/invoice → pour le client (par ID)
· GET /orders/by-number/{orderNumber}/invoice → pour le client (par numéro)

5. Refactoring bootstrap/app.php ✅

Fichier refait :

· bootstrap/app.php ✅ REFAIT

Changement critique :

· Ajout d'un renderer pour AuthenticationException
· Renvoie du JSON 401 au lieu d'essayer de rediriger vers /login

Code ajouté :

```php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (AuthenticationException $e, Request $request) {
        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié. Veuillez vous connecter.',
            ], 401);
        }
    });
})
```

Sans ça : erreur 500 "Route [login] not defined" au lieu de 401.

---

🔧 CONFIGURATION CRITIQUE

Fichier .env

```env
APP_NAME="ShopPro API"
APP_ENV=local
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=shoppro_db
DB_USERNAME=root
DB_PASSWORD=
```

Lancer le serveur

⚠️ Dans un terminal dédié (laisser ouvert) :

```cmd
cd C:\Users\barak\shoppro_api
php artisan serve
```

Résultat : Starting Laravel development server: http://127.0.0.1:8000

Vider les caches (si problème)

```cmd
cd C:\Users\barak\shoppro_api
php artisan optimize:clear
```

Cette commande vide :

· config cache
· route cache
· view cache
· event cache
· application cache

Créer un lien symbolique pour les images

⚠️ Une seule fois (si pas déjà fait) :

```cmd
cd C:\Users\barak\shoppro_api
php artisan storage:link
```

---

📂 STRUCTURE DES DOSSIERS

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── AuthController.php            # Auth (login, register, profile)
│   │       ├── CategoryController.php        # Catégories
│   │       ├── BrandController.php           # Marques
│   │       ├── Address/                      # ✅ NOUVEAU
│   │       │   └── AddressController.php
│   │       ├── Admin/                        # Admin complet
│   │       │   ├── AdminOrderController.php
│   │       │   ├── AdminReviewController.php
│   │       │   ├── AdminStockController.php
│   │       │   ├── AdminPromotionController.php
│   │       │   ├── AdminSettingController.php
│   │       │   ├── AdminNotificationController.php
│   │       │   └── AdminUserController.php
│   │       ├── Banner/                       # Bannières
│   │       │   └── BannerController.php
│   │       ├── Dashboard/                    # Stats
│   │       │   └── DashboardController.php
│   │       ├── Legal/                        # Documents légaux
│   │       │   └── LegalDocumentController.php
│   │       ├── Order/                        # ✅ REFONDU
│   │       │   └── OrderController.php
│   │       ├── Product/                      # Produits
│   │       │   └── ProductController.php
│   │       ├── Review/                       # ✅ REFONDU
│   │       │   └── ReviewController.php
│   │       └── Support/                      # Support
│   │           ├── ContactController.php
│   │           ├── FaqController.php
│   │           ├── FaqCategoryController.php
│   │           ├── SupportTicketController.php
│   │           └── TicketMessageController.php
│   │
│   ├── Middleware/
│   │   ├── CheckRole.php                     # role:admin, role:seller
│   │   └── CorsMiddleware.php
│   │
│   ├── Requests/
│   │   └── Api/
│   │       ├── Auth/                         # 6 requests
│   │       ├── Address/                      # ✅ NOUVEAU
│   │       │   ├── StoreAddressRequest.php
│   │       │   └── UpdateAddressRequest.php
│   │       ├── Review/                       # ✅ NOUVEAU
│   │       │   └── StoreReviewRequest.php
│   │       └── Support/                      # 3 requests
│   │
│   └── Resources/
│       └── Api/
│           ├── Auth/                         # 2 resources
│           ├── Address/                      # ✅ NOUVEAU
│           │   └── UserAddressResource.php
│           ├── Product/                      # 4 resources
│           ├── Review/                       # ✅ NOUVEAU
│           │   └── ReviewResource.php
│           ├── Legal/                        # 1 resource
│           └── Support/                      # 4 resources
│
├── Models/
│   ├── User.php
│   ├── RefreshToken.php
│   ├── User/                                 # User, Profile, Address, Role, Permission
│   ├── Catalog/                              # Product, Category, Brand, etc.
│   ├── Order/                                # Order, OrderItem, Cart, etc.
│   ├── Payment/                              # Payment, Transaction, Refund
│   ├── Review/                               # Review, ReviewImage, ReviewVote, Favorite
│   ├── Marketing/                            # Banner, Coupon, Promotion, FlashSale
│   ├── Support/                              # Notification, SupportTicket, Faq
│   ├── Admin/                                # AdminLog, Setting, Currency
│   └── Config/                               # Language, Country, City
│
├── Providers/
│   └── AppServiceProvider.php
│
database/
├── migrations/         # 60+ migrations
├── seeders/            # 47 seeders
│
resources/
├── views/
│   └── invoices/                             # ✅ NOUVEAU
│       └── order.blade.php
│
routes/
└── api.php                                   # Toutes les routes API
```

---

📋 ROUTES API PRINCIPALES

🔓 Routes publiques

Méthode URL Description
POST /auth/register Inscription
POST /auth/login Connexion
POST /auth/forgot-password Mot de passe oublié
POST /auth/reset-password Réinitialisation
POST /auth/refresh-token Rafraîchir le token
GET /products Liste paginée
GET /products/{id} Détails
GET /products/featured En vedette
GET /products/search Recherche
GET /products/filter Filtres
GET /products/{id}/reviews Avis d'un produit
GET /categories Catégories
GET /brands Marques
GET /banners Bannières

🔒 Routes protégées (auth:sanctum)

Méthode URL Description
POST /auth/logout Déconnexion
GET /auth/profile Voir profil
PUT /auth/profile Modifier profil
POST /auth/change-password Changer mot de passe
POST /auth/upload-avatar Upload avatar
GET /orders Mes commandes
POST /orders Créer une commande (avec items[])
GET /orders/{id} Détails commande
POST /orders/{id}/cancel Annuler
GET /orders/{id}/track Suivi
GET /orders/{id}/invoice Facture PDF (par ID)
GET /orders/by-number/{orderNumber}/invoice Facture PDF (par numéro)
GET /addresses Mes adresses
POST /addresses Créer une adresse
GET /addresses/{id} Détails adresse
PUT /addresses/{id} Modifier une adresse
DELETE /addresses/{id} Supprimer une adresse
POST /addresses/{id}/set-default Définir par défaut
POST /reviews Créer un avis (avec photos)
PUT /reviews/{id} Modifier un avis
DELETE /reviews/{id} Supprimer un avis
POST /reviews/{id}/vote Voter utile/pas utile
GET /favorites Mes favoris
POST /favorites/toggle/{productId} Ajouter/retirer

👑 Routes admin (auth:sanctum + role:admin)

Toutes les routes sous /admin/* :

· /admin/products (CRUD)
· /admin/categories (CRUD)
· /admin/brands (CRUD)
· /admin/banners (CRUD)
· /admin/orders (CRUD + actions)
· /admin/orders/{id}/invoice (facture PDF)
· /admin/users (CRUD)
· /admin/reviews (modération)
· /admin/stocks (gestion)
· /admin/promotions (CRUD)
· /admin/settings (CRUD)
· /admin/notifications (CRUD)
· /admin/dashboard/* (stats)
· /admin/stats/* (statistiques)

---

🗄️ BASE DE DONNÉES

Tables principales

Catégorie Tables
Utilisateurs users, user_profiles, user_addresses, roles, permissions, role_user, permission_role, refresh_tokens
Catalogue products, categories, subcategories, brands, product_images, product_variants, product_attributes, product_specifications, tags, product_tag
Stock warehouses, stocks, stock_movements, suppliers
Commandes orders, order_items, order_status_histories, order_trackings, carts, cart_items, shipping_methods
Paiement payments, payment_methods, transactions, refunds
Avis reviews, review_images, review_votes, favorites
Marketing banners, coupons, promotions, flash_sales, newsletter_subscribers
Support notifications, support_tickets, ticket_messages, faqs, faq_categories
Admin admin_logs, settings, currencies
Config languages, countries, cities
Legal legal_documents

Comptes de test

Rôle Email Mot de passe
Admin admin@shoppro.com password123
Vendeur vendeur@shoppro.com password123
Client jean@example.com password123
Client marie@example.com password123

---

⚠️ CONVENTIONS À RESPECTER

Pattern des Controllers

```php
public function index(Request $request)
{
    try {
        // Logique
        return response()->json([
            'success' => true,
            'message' => 'Message de succès',
            'data' => $data,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur',
            'error' => $e->getMessage(),
        ], 500);
    }
}
```

Règles :

· Toujours try/catch
· Toujours DB::beginTransaction() pour les opérations multiples
· Toujours DB::rollBack() dans le catch
· Toujours retourner {success, message, data}
· Pagination : {data, pagination: {total, count, per_page, current_page, last_page, has_more}}

Pattern des Requests

```php
class XxxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    
    public function rules(): array
    {
        return [
            'field' => 'required|string|max:255',
        ];
    }
    
    public function messages(): array
    {
        return [
            'field.required' => 'Message en français',
        ];
    }
    
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
```

Pattern des Resources

```php
class XxxResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            // ...
        ];
    }
    
    public function with(Request $request): array
    {
        return ['success' => true];
    }
}
```

---

🐛 BUGS CONNUS

Bug Fichier Priorité
Certains seeders créent des doublons database/seeders/* 🟢 Basse
Images = test.jpg Tous les produits 🟡 Moyenne
fileinfo chargé 2x php.ini 🟢 Basse (warning only)
Retours commandes partiels OrderController 🟡 Moyenne

---

✅ CE QUI FONCTIONNE PARFAITEMENT

· ✅ Authentification Sanctum + RefreshToken
· ✅ Produits (CRUD complet, filtres, recherche)
· ✅ Catégories, Marques, Bannières
· ✅ Commandes (création avec items, annulation, suivi)
· ✅ Adresses (CRUD complet, défaut intelligent)
· ✅ Avis (avec photos, votes, statistiques)
· ✅ PDF Facture (3 endpoints)
· ✅ Admin (dashboards, stats, CRUD)
· ✅ Support (tickets, FAQ)
· ✅ Legal (documents)

---

🎯 PROCHAINES ÉTAPES

# Phase Priorité
1 Paiement réel (Stripe, Mobile Money) 🔴 Haute
2 FCM Push notifications 🔴 Haute
3 Retours commandes (endpoints) 🟡 Moyenne
4 Google Maps (adresses) 🟡 Moyenne
5 Statistiques avancées 🟢 Basse
6 Tests (PHPUnit) 🟢 Basse
7 Déploiement production 🟢 Basse

---

🎯 COMMENT CONTINUER DANS UN NOUVEAU CHAT

Message initial à donner

Je reprends le développement de ShopPro API (backend Laravel).

Je te fournis la documentation complète (docs 00 à 10).

État actuel : ~85% terminé.

Ce qui a été fait récemment (03/10/2026) :

· AddressController créé de zéro
· OrderController::store() refondu (items directs)
· ReviewController refondu (avec photos)
· PDF Facture avec barryvdh/laravel-dompdf
· bootstrap/app.php refondu (401 JSON)

Ce que je veux faire : [ta phase].

Règles IMPORTANTES :

· Ne JAMAIS casser ce qui fonctionne
· Toujours renvoyer les fichiers COMPLETS
· Demander les fichiers manquants avant de modifier
· Respecter les conventions (voir doc 10)
· Toujours try/catch + DB::beginTransaction()
· Toujours retourner {success, message, data}

Comportement attendu

Le nouveau chat doit :

1. Lire tous les docs avant de toucher au code
2. Comprendre l'architecture Controller/Request/Resource
3. Respecter les conventions (try/catch, transactions, JSON)
4. Ne jamais casser ce qui fonctionne
5. Toujours renvoyer les fichiers complets
6. Demander les fichiers manquants avant de modifier
7. Tester au fur et à mesure

Commandes utiles

```cmd
REM Lancer le serveur
cd C:\Users\barak\shoppro_api
php artisan serve

REM Vider les caches
php artisan optimize:clear

REM Voir les routes
php artisan route:list

REM Voir les routes filtrées
php artisan route:list | findstr "invoice"

REM Voir les logs
type storage\logs\laravel.log

REM MySQL
mysql -u root shoppro_db
```

Fichiers à connaître en priorité

Fichier Rôle
routes/api.php Toutes les routes
bootstrap/app.php Config middleware + exceptions
app/Http/Controllers/Api/Order/OrderController.php Commandes
app/Http/Controllers/Api/Address/AddressController.php Adresses
app/Http/Controllers/Api/Review/ReviewController.php Avis
resources/views/invoices/order.blade.php Template facture

---

📊 STATISTIQUES DU PROJET

· Contrôleurs : ~20
· Requests : ~15
· Resources : ~13
· Modèles : ~48
· Migrations : ~60
· Seeders : ~47
· Routes : ~245
· Tables MySQL : ~50

---

🔑 LES 6 POINTS CRITIQUES À RETENIR

1. bootstrap/app.php DOIT avoir le renderer pour AuthenticationException
   → Sinon : erreur 500 "Route [login] not defined"
2. OrderController::store() accepte items[] dans le body
   → Ne dépend PAS du panier Laravel
3. Le serveur calcule TOUS les totaux
   → Sécurité : ne jamais faire confiance au client
4. Le serveur vérifie et décrémente le stock
   → Éviter les commandes de produits indisponibles
5. Toujours DB::beginTransaction() + rollBack()
   → Éviter les données corrompues en cas d'erreur
6. Toujours {success, message, data} dans les réponses
   → Cohérence de l'API

---

🎉 BASE DE CONNAISSANCES API

Après avoir lu ce document + les docs 00 à 08, tu as toutes les informations pour continuer le développement de ShopPro API sans casser ce qui fonctionne.

Prochaine phase recommandée : Paiement réel (Stripe / Mobile Money)

---

Dernière mise à jour : 03/10/2026
Auteur : Baraka Ntwali
Version : 1.0

```

---

# 🎯 INSTRUCTIONS

## 1. Crée le fichier

```cmd
notepad C:\Users\barak\docs\10-fichiers-cles-api.md
```

Puis colle le contenu ci-dessus.

2. Sauvegarde (Ctrl+S)

3. Vérifie que tu as bien les 2 fichiers

```cmd
dir C:\Users\barak\docs\10-fichiers-cles*.md
```

Résultat attendu :

```
10-fichiers-cles-mobile.md
10-fichiers-cles-api.md
```

---

📋 RÉCAPITULATIF — TA BASE DE CONNAISSANCES COMPLÈTE

📱 Mobile (Flutter)

# Fichier Statut
00 00-contexte.md ✅
01 01-architecture.md ✅
02 02-structure.md ✅
03 03-providers.md ✅
04 04-routes.md ✅
05 05-widgets.md ✅
06 06-progress.md ✅
07 07-prochaines-etapes.md ✅
08 08-conventions.md ✅
09 09-fichiers-cles.md ✅
10 10-fichiers-cles-mobile.md ✅ NOUVEAU

🖥️ API (Laravel)

# Fichier Statut
00 00-contexte.md ✅
01 01-architecture.md ✅
02 02-database.md ✅
02b 02b-colonnes-tables.md ✅
03 03-routes-api.md ✅
04 04-controllers.md ✅
05 05-models.md ✅
06 06-resources.md ✅
07 07-progress.md ✅
08 08-prochaines-etapes.md ✅
10 10-fichiers-cles-api.md ✅ NOUVEAU

→ Tu as maintenant 22 documents au total.

---

🎯 POUR UN NOUVEAU CHAT

Message à envoyer au nouveau chat :

Je reprends le développement de ShopPro (application e-commerce Flutter + Laravel).

Je te fournis 22 documents de documentation (00 à 10 pour Mobile et API).

À lire AVANT toute modification :

1. 10-fichiers-cles-mobile.md — État actuel Mobile
2. 10-fichiers-cles-api.md — État actuel API
3. Les autres docs (00 à 09) pour le contexte

Règles STRICTES :

· Ne JAMAIS casser ce qui fonctionne
· Toujours renvoyer les fichiers COMPLETS (pas de "...")
· Demander les fichiers manquants avant de modifier
· Respecter les conventions (voir docs 08 et 10)
· Toujours mettre un / au début des endpoints Flutter
· Toujours try/catch + DB::beginTransaction() côté Laravel

Prochaine phase : [Paiement WebView / autre]

Commence par me confirmer que tu as bien lu et compris.

---

🎉 BILAN DE CETTE SESSION

Aujourd'hui (03/10/2026), on a accompli :

· ✅ Refonte complète des Commandes (API)
· ✅ Création complète des Adresses (API)
· ✅ Refonte complète des Avis (photos + votes)
· ✅ Création de la Facture PDF (backend + frontend)
· ✅ Refonte de bootstrap/app.php (401 JSON)
· ✅ Fix du / manquant dans les endpoints
· ✅ Création de 2 documents de continuité

Le projet est prêt à être repris dans un nouveau chat.

---

Dis-moi "OK" quand tu as créé le fichier API → on peut conclure ou continuer sur une autre phase. 🚀

