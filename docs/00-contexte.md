📦 FICHIER : docs/00-contexte.md (Laravel API)

---

```markdown
# 📚 CONTEXTE DU PROJET - SHOPPRO API

---

## 🖥️ PRÉSENTATION GÉNÉRALE

**ShopPro API** est le backend Laravel de l'application e-commerce ShopPro.

---

## 🛠️ TECHNOLOGIES

| Technologie | Version | Usage |
|-------------|---------|-------|
| Laravel | 13.x | Framework API |
| PHP | 8.5 | Langage |
| MySQL | 8.x | Base de données |
| Sanctum | - | Authentification par tokens |
| Eloquent | - | ORM |

---

## 📂 STRUCTURE DES DOSSIERS

```

shoppro_api/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       ├── Auth/
│   │   │       │   └── AuthController.php
│   │   │       ├── Product/
│   │   │       │   └── ProductController.php
│   │   │       ├── Category/
│   │   │       │   └── CategoryController.php
│   │   │       └── Brand/
│   │   │           └── BrandController.php
│   │   │
│   │   ├── Requests/
│   │   │   └── Api/
│   │   │       └── Auth/
│   │   │           ├── RegisterRequest.php
│   │   │           ├── LoginRequest.php
│   │   │           ├── UpdateProfileRequest.php
│   │   │           ├── ChangePasswordRequest.php
│   │   │           ├── ForgotPasswordRequest.php
│   │   │           └── ResetPasswordRequest.php
│   │   │
│   │   ├── Resources/
│   │   │   └── Api/
│   │   │       ├── Auth/
│   │   │       │   ├── UserResource.php
│   │   │       │   └── AuthResource.php
│   │   │       └── Product/
│   │   │           ├── ProductResource.php
│   │   │           ├── ProductCollection.php
│   │   │           ├── CategoryResource.php
│   │   │           └── BrandResource.php
│   │   │
│   │   └── Middleware/
│   │       ├── CheckRole.php
│   │       └── CorsMiddleware.php
│   │
│   └── Models/
│       ├── User.php
│       ├── RefreshToken.php
│       ├── User/
│       │   ├── UserProfile.php
│       │   ├── UserAddress.php
│       │   ├── Role.php
│       │   └── Permission.php
│       ├── Catalog/
│       │   ├── Category.php
│       │   ├── Subcategory.php
│       │   ├── Brand.php
│       │   ├── Product.php
│       │   ├── ProductImage.php
│       │   ├── ProductVariant.php
│       │   ├── ProductAttribute.php
│       │   ├── ProductSpecification.php
│       │   ├── Tag.php
│       │   ├── Warehouse.php
│       │   ├── Stock.php
│       │   └── StockMovement.php
│       ├── Order/
│       │   ├── Cart.php
│       │   ├── CartItem.php
│       │   ├── Order.php
│       │   ├── OrderItem.php
│       │   ├── OrderStatusHistory.php
│       │   ├── ShippingMethod.php
│       │   ├── OrderTracking.php
│       │   └── Supplier.php
│       ├── Payment/
│       │   ├── Payment.php
│       │   ├── PaymentMethod.php
│       │   ├── Transaction.php
│       │   └── Refund.php
│       ├── Review/
│       │   ├── Favorite.php
│       │   ├── Review.php
│       │   ├── ReviewImage.php
│       │   └── ReviewVote.php
│       ├── Marketing/
│       │   ├── Coupon.php
│       │   ├── Promotion.php
│       │   ├── FlashSale.php
│       │   └── Banner.php
│       ├── Support/
│       │   ├── Notification.php
│       │   ├── SupportTicket.php
│       │   ├── TicketMessage.php
│       │   └── Faq.php
│       ├── Admin/
│       │   ├── AdminLog.php
│       │   ├── Setting.php
│       │   ├── NewsletterSubscriber.php
│       │   └── Currency.php
│       └── Config/
│           ├── Language.php
│           ├── Country.php
│           └── City.php
│
├── database/
│   ├── migrations/     # 50+ migrations
│   └── seeders/        # 44 seeders
│
├── routes/
│   └── api.php         # Toutes les routes API
│
├── config/
│   ├── cors.php
│   └── sanctum.php
│
└── .env                # Configuration

```

---

## ⚙️ CONFIGURATION

### Fichier `.env`

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

---

🔐 AUTHENTIFICATION

Méthode : Laravel Sanctum

Fonctionnalité Endpoint Statut
Inscription POST /api/v1/auth/register ✅
Connexion POST /api/v1/auth/login ✅
Déconnexion POST /api/v1/auth/logout ✅
Profil GET /api/v1/auth/profile ✅
Refresh Token POST /api/v1/auth/refresh-token ✅

---

🗄️ BASE DE DONNÉES

Nom : shoppro_db

Tables : 50+ migrations

Catégorie Nombre de tables
Utilisateurs 4
Catalogue 10
Stock 4
Commandes 7
Paiement 4
Avis 4
Marketing 5
Support 4
Admin 5
Configuration 5

---

📊 SEEDERS

44 seeders créés :

Catégorie Seeders
Rôles et permissions 1
Paramètres 1
Devises 1
Langues 1
Pays 1
Villes 1
Livraison 1
Paiement 1
Catégories 1
Sous-catégories 1
Marques 1
Tags 1
Produits 1 (11 produits)
Images produits 1
Variantes 1
Attributs 1
Entrepôts 1
Stocks 1
Fournisseurs 1
Mouvements stock 1
Utilisateurs 1 (10 comptes)
Profils 1
Adresses 1
Favoris 1
Paniers 1
Éléments panier 1
Commandes 1
Éléments commande 1
Avis 1
Images avis 1
Votes avis 1
Coupons 1
Promotions 1
Ventes flash 1
Bannières 1
Newsletter 1
Notifications 1
Tickets 1
Messages tickets 1
FAQ 1
Paiements 1
Transactions 1
Remboursements 1
Logs admin 1

---

🔗 LIENS IMPORTANTS

Serveur

```bash
php artisan serve
# → http://127.0.0.1:8000
```

Base de données

```bash
mysql -u root shoppro_db
```

Routes

```bash
php artisan route:list
```

---

📝 NOTES IMPORTANTES

1. Images : Utilise test.jpg pour tous les produits
2. APP_URL : http://127.0.0.1:8000
3. CORS : Autorisé pour toutes les origines
4. SoftDeletes : Utilisé sur la plupart des modèles
5. Sanctum : Tokens avec expiration
6. RefreshToken : Modèle custom pour refresh tokens

```

---

## 📋 RÉSUMÉ

Ce fichier documente :
- ✅ La structure complète de l'API
- ✅ La configuration
- ✅ L'authentification
- ✅ La base de données
- ✅ Les seeders

---

**Veux-tu le contenu du fichier `01-architecture.md` pour l'API ?** 🚀