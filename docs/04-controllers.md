📦 FICHIER : docs/04-controllers.md (Laravel API)

---

```markdown
# 🎮 CONTRÔLEURS - SHOPPRO API

---

## 📋 LISTE DES CONTRÔLEURS

| Contrôleur | Fichier | Rôle |
|------------|---------|------|
| AuthController | `Auth/AuthController.php` | Authentification |
| ProductController | `Product/ProductController.php` | Produits |
| CategoryController | `Category/CategoryController.php` | Catégories |
| BrandController | `Brand/BrandController.php` | Marques |

---

## 🔐 AuthController

### Fichier : `app/Http/Controllers/Api/Auth/AuthController.php`

### Méthodes :

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `register()` | POST /auth/register | Inscription + RefreshToken |
| `login()` | POST /auth/login | Connexion + RefreshToken |
| `logout()` | POST /auth/logout | Révocation du token courant |
| `logoutAll()` | POST /auth/logout-all | Révocation de tous les tokens |
| `profile()` | GET /auth/profile | Profil utilisateur |
| `updateProfile()` | PUT /auth/profile | Mise à jour profil |
| `changePassword()` | POST /auth/change-password | Changement mot de passe |
| `forgotPassword()` | POST /auth/forgot-password | Envoi lien réinitialisation |
| `resetPassword()` | POST /auth/reset-password | Réinitialisation |
| `refreshToken()` | POST /auth/refresh-token | Rafraîchir le token |
| `uploadAvatar()` | POST /auth/upload-avatar | Upload photo |
| `checkToken()` | GET /auth/check-token | Vérifier token |
| `verifyEmail()` | POST /auth/verify-email | Vérifier email |
| `resendVerification()` | POST /auth/resend-verification | Renvoyer vérification |
| `listUsers()` | GET /admin/users | Liste utilisateurs (admin) |
| `showUser()` | GET /admin/users/{id} | Détails (admin) |
| `updateUser()` | PUT /admin/users/{id} | Modifier (admin) |
| `deleteUser()` | DELETE /admin/users/{id} | Supprimer (admin) |
| `changeUserRole()` | PUT /admin/users/{id}/role | Changer rôle (admin) |

### Imports nécessaires :
```php
use App\Models\User;
use App\Models\RefreshToken;
use App\Models\User\UserProfile;
use App\Models\User\Role;
use App\Http\Resources\Api\Auth\UserResource;
use App\Http\Resources\Api\Auth\AuthResource;
```

---

📦 ProductController

Fichier : app/Http/Controllers/Api/Product/ProductController.php

Méthodes :

Méthode Endpoint Description
index() GET /products Liste paginée
show() GET /products/{id} Détails + vues++
featured() GET /products/featured En vedette
popular() GET /products/popular Populaires (par ventes)
recent() GET /products/recent Récents
onSale() GET /products/on-sale En promotion
search() GET /products/search Recherche
filter() GET /products/filter Filtres avancés
byCategory() GET /products/category/{id} Par catégorie
byBrand() GET /products/brand/{id} Par marque
similar() GET /products/{id}/similar Similaires
productReviews() GET /products/{id}/reviews Avis
store() POST /admin/products Créer (admin)
update() PUT /admin/products/{id} Modifier (admin)
destroy() DELETE /admin/products/{id} Supprimer (admin)
restore() POST /admin/products/{id}/restore Restaurer
uploadImages() POST /admin/products/{id}/images Upload images
deleteImage() DELETE /admin/products/{id}/images/{imageId} Supprimer image
sellerProducts() GET /seller/products Produits vendeur
stats() GET /admin/stats/products Statistiques

Imports nécessaires :

```php
use App\Models\Catalog\Product;
use App\Models\Catalog\Category;
use App\Models\Catalog\Brand;
use App\Http\Resources\Api\Product\ProductResource;
use App\Http\Resources\Api\Product\ProductCollection;
```

Notes importantes :

· index() utilise with(['category', 'brand', 'images'])
· show() incrémente views_count
· show() charge reviews avec take(10) (⚠️ pas de take() - bug SQL)
· filter() supporte les filtres complets

---

🏷️ CategoryController

Fichier : app/Http/Controllers/Api/Category/CategoryController.php

Méthodes :

Méthode Endpoint Description
index() GET /categories Liste avec withCount('products')
show() GET /categories/{id} Détails + sous-catégories
products() GET /categories/{id}/products Produits paginés
subcategories() GET /categories/{id}/subcategories Sous-catégories
store() POST /admin/categories Créer (admin)
update() PUT /admin/categories/{id} Modifier (admin)
destroy() DELETE /admin/categories/{id} Supprimer (admin)
stats() GET /admin/stats/categories Statistiques

Imports nécessaires :

```php
use App\Models\Catalog\Category;
use App\Models\Catalog\Product;
use Illuminate\Support\Str;
```

Notes importantes :

· index() utilise withCount('products') pour le compteur
· index() supporte les filtres parent_id, root, featured

---

🏪 BrandController

Fichier : app/Http/Controllers/Api/Brand/BrandController.php

Méthodes :

Méthode Endpoint Description
index() GET /brands Liste
show() GET /brands/{id} Détails + stats
products() GET /brands/{id}/products Produits paginés
store() POST /admin/brands Créer (admin)
update() PUT /admin/brands/{id} Modifier (admin)
destroy() DELETE /admin/brands/{id} Supprimer (admin)
stats() GET /admin/stats/brands Statistiques

Imports nécessaires :

```php
use App\Models\Catalog\Brand;
use App\Models\Catalog\Product;
use Illuminate\Support\Str;
```

---

📝 PATTERN DE RÉPONSE

Tous les contrôleurs suivent ce pattern :

```php
// Succès
return response()->json([
    'success' => true,
    'message' => 'Message de succès',
    'data' => $data,
], 200);

// Erreur
return response()->json([
    'success' => false,
    'message' => 'Message d\'erreur',
    'error' => $e->getMessage(),
], 500);
```

```

---

## 📋 RÉSUMÉ

Ce fichier documente :
- ✅ Les 4 contrôleurs
- ✅ Toutes les méthodes
- ✅ Les imports nécessaires
- ✅ Les notes importantes
- ✅ Le pattern de réponse

---

**Veux-tu le contenu du fichier `05-models.md` ?** 🚀