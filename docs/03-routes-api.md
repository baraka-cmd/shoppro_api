📦 FICHIER : docs/03-routes-api.md (Laravel API)

---

```markdown
# 🧭 ROUTES API - SHOPPRO

---

## 📋 URL DE BASE

```

http://127.0.0.1:8000/api/v1

```

---

## 🔐 AUTHENTIFICATION

### Routes publiques (sans token)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/auth/register` | Inscription |
| POST | `/auth/login` | Connexion |
| POST | `/auth/forgot-password` | Mot de passe oublié |
| POST | `/auth/reset-password` | Réinitialisation |
| POST | `/auth/refresh-token` | Rafraîchir le token |
| POST | `/auth/verify-email` | Vérifier email |
| POST | `/auth/resend-verification` | Renvoyer vérification |

### Routes protégées (auth:sanctum)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/auth/logout` | Déconnexion |
| POST | `/auth/logout-all` | Déconnexion totale |
| GET | `/auth/profile` | Voir profil |
| PUT | `/auth/profile` | Modifier profil |
| POST | `/auth/change-password` | Changer mot de passe |
| POST | `/auth/upload-avatar` | Upload avatar |
| GET | `/auth/check-token` | Vérifier token |

---

## 📦 PRODUITS

### Routes publiques

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/products` | Liste paginée |
| GET | `/products/{id}` | Détails |
| GET | `/products/featured` | En vedette |
| GET | `/products/popular` | Populaires |
| GET | `/products/recent` | Récents |
| GET | `/products/on-sale` | En promotion |
| GET | `/products/search` | Recherche |
| GET | `/products/filter` | Filtres avancés |
| GET | `/products/category/{id}` | Par catégorie |
| GET | `/products/brand/{id}` | Par marque |
| GET | `/products/{id}/similar` | Similaires |
| GET | `/products/{id}/reviews` | Avis du produit |

### Routes admin (auth:sanctum + role:admin)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/admin/products` | Créer |
| PUT | `/admin/products/{id}` | Modifier |
| DELETE | `/admin/products/{id}` | Supprimer |
| POST | `/admin/products/{id}/restore` | Restaurer |
| POST | `/admin/products/{id}/images` | Upload images |
| DELETE | `/admin/products/{id}/images/{imageId}` | Supprimer image |

### Routes vendeur (auth:sanctum + role:seller,admin)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/seller/products` | Produits du vendeur |
| POST | `/seller/products` | Créer |
| PUT | `/seller/products/{id}` | Modifier |
| DELETE | `/seller/products/{id}` | Supprimer |

---

## 🏷️ CATÉGORIES

### Routes publiques

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/categories` | Liste |
| GET | `/categories/{id}` | Détails |
| GET | `/categories/{id}/products` | Produits |
| GET | `/categories/{id}/subcategories` | Sous-catégories |

### Routes admin

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/admin/categories` | Créer |
| PUT | `/admin/categories/{id}` | Modifier |
| DELETE | `/admin/categories/{id}` | Supprimer |

---

## 🏪 MARQUES

### Routes publiques

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/brands` | Liste |
| GET | `/brands/{id}` | Détails |
| GET | `/brands/{id}/products` | Produits |

### Routes admin

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/admin/brands` | Créer |
| PUT | `/admin/brands/{id}` | Modifier |
| DELETE | `/admin/brands/{id}` | Supprimer |

---

## ❤️ FAVORIS

### Routes protégées (auth:sanctum)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/favorites` | Liste |
| POST | `/favorites/toggle/{productId}` | Toggle |
| DELETE | `/favorites/{id}` | Supprimer |

---

## ⭐ AVIS

### Routes publiques

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/products/{id}/reviews` | Avis du produit |

### Routes protégées (auth:sanctum)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/reviews` | Créer avis |
| PUT | `/reviews/{id}` | Modifier avis |
| DELETE | `/reviews/{id}` | Supprimer avis |

---

## 👑 ROUTES ADMIN (utilisateurs)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/admin/users` | Liste utilisateurs |
| GET | `/admin/users/{id}` | Détails |
| PUT | `/admin/users/{id}` | Modifier |
| DELETE | `/admin/users/{id}` | Supprimer |
| PUT | `/admin/users/{id}/role` | Changer rôle |

---

## 📊 STATISTIQUES

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/admin/stats/products` | Stats produits |
| GET | `/admin/stats/categories` | Stats catégories |
| GET | `/admin/stats/brands` | Stats marques |

---

## 📋 FILTRES DISPONIBLES

### GET `/products/filter`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `category_id` | int | Filtrer par catégorie |
| `brand_id` | int | Filtrer par marque |
| `min_price` | float | Prix minimum |
| `max_price` | float | Prix maximum |
| `in_stock` | bool | En stock uniquement |
| `min_rating` | float | Note minimale |
| `on_sale` | bool | En promotion |
| `sort_by` | string | Tri (price, name, rating, popularity) |
| `sort_direction` | string | asc ou desc |
| `page` | int | Numéro de page |
| `per_page` | int | Éléments par page |

---

## 📋 RÉSUMÉ

Ce fichier documente :
- ✅ Les 40+ routes API
- ✅ Les endpoints par catégorie
- ✅ Les middlewares requis
- ✅ Les filtres disponibles
```

---

## 🔄 ROUTES AJOUTÉES (31/08/2026)

### 🎠 Bannières

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/banners` | Liste des bannières actives |
| GET | `/banners/{id}` | Détails d'une bannière |
| POST | `/admin/banners` | Créer (admin) |
| PUT | `/admin/banners/{id}` | Modifier (admin) |
| DELETE | `/admin/banners/{id}` | Supprimer (admin) |
```