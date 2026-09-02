📦 FICHIER : docs/06-resources.md (Laravel API)

---

```markdown
# 📦 RESOURCES API - SHOPPRO

---

## 📋 LISTE DES RESOURCES

| Resource | Fichier | Rôle |
|----------|---------|------|
| UserResource | `Auth/UserResource.php` | Formatage utilisateur |
| AuthResource | `Auth/AuthResource.php` | Formatage authentification |
| ProductResource | `Product/ProductResource.php` | Formatage produit |
| ProductCollection | `Product/ProductCollection.php` | Collection produits |
| CategoryResource | `Product/CategoryResource.php` | Formatage catégorie |
| BrandResource | `Product/BrandResource.php` | Formatage marque |

---

## 👤 UserResource

### Fichier : `app/Http/Resources/Api/Auth/UserResource.php`

### Champs retournés :

```json
{
    "id": 1,
    "name": "Admin ShopPro",
    "full_name": "Admin ShopPro",
    "email": "admin@shoppro.com",
    "avatar": "http://127.0.0.1:8000/images/default-avatar.png",
    "email_verified_at": null,
    "profile": {
        "first_name": "Admin",
        "last_name": "ShopPro",
        "phone": "+237 6 50 00 00 00",
        "birth_date": "1990-01-01",
        "gender": "male",
        "country": "Cameroun",
        "city": "Douala",
        "address": "Akwa, Douala",
        "language": "fr",
        "currency": "FCFA"
    },
    "roles": [
        {
            "id": 1,
            "name": "admin",
            "display_name": "Administrateur"
        }
    ],
    "stats": {
        "total_orders": 0,
        "total_spent": 0
    },
    "created_at": "2026-08-14T21:58:09.000000Z"
}
```

---

🔐 AuthResource

Fichier : app/Http/Resources/Api/Auth/AuthResource.php

Champs retournés :

```json
{
    "user": {
        "id": 1,
        "name": "Admin ShopPro",
        "email": "admin@shoppro.com",
        "profile": {...},
        "roles": [...]
    },
    "token": "2|avF6a4PyENi0IIpL8nBvVbMdwZJdDkmLP9K5WZH04c2482f5",
    "refresh_token": "7854cf00521f9ebe004ff24c07da73c7f1dda4baa6fe4e733d0b8946079b5584",
    "expires_at": "2026-09-16T20:08:30.802232Z"
}
```

---

📦 ProductResource

Fichier : app/Http/Resources/Api/Product/ProductResource.php

Champs retournés :

```json
{
    "id": 1,
    "name": "Samsung Galaxy S24 Ultra",
    "slug": "samsung-galaxy-s24-ultra",
    "short_description": "Smartphone premium avec S-Pen intégré",
    "description": "Le Samsung Galaxy S24 Ultra est...",
    
    "price": 850000,
    "price_formatted": "850 000 FCFA",
    "discount_price": 799000,
    "discount_price_formatted": "799 000 FCFA",
    "discount_percentage": 6,
    "current_price": 799000,
    "current_price_formatted": "799 000 FCFA",
    
    "stock": 50,
    "is_in_stock": true,
    "min_stock": 10,
    "stock_status": "in_stock",
    
    "sku": "SAM-S24U-001",
    "barcode": "8806095432100",
    
    "category": {
        "id": 1,
        "name": "Électronique",
        "slug": "electronique",
        "image": "http://127.0.0.1:8000/storage/categories/test.jpg"
    },
    "brand": {
        "id": 1,
        "name": "Samsung",
        "slug": "samsung",
        "logo": "http://127.0.0.1:8000/storage/brands/test.jpg"
    },
    "images": [
        {
            "id": 1,
            "url": "http://127.0.0.1:8000/storage/products/test.jpg",
            "thumbnail": "http://127.0.0.1:8000/storage/products/test.jpg",
            "is_primary": true
        }
    ],
    
    "main_image": "http://127.0.0.1:8000/storage/products/test.jpg",
    "main_image_thumbnail": "http://127.0.0.1:8000/storage/products/test.jpg",
    
    "views_count": 0,
    "sales_count": 0,
    "favorites_count": 0,
    "average_rating": 4,
    "reviews_count": 6,
    
    "is_featured": true,
    "is_new": true,
    "is_bestseller": true,
    "is_on_sale": true,
    "badges": {
        "featured": true,
        "new": true,
        "bestseller": true,
        "sale": true
    },
    
    "is_active": true,
    "is_available": true,
    
    "created_at": "2026-08-14T21:35:25.000000Z"
}
```

⚠️ IMPORTANT : Images

Les URLs d'images utilisent asset('storage/' . $path) pour générer des URLs complètes :

```php
'main_image' => $this->main_image 
    ? asset('storage/' . $this->main_image) 
    : null,
```

---

📦 ProductCollection

Fichier : app/Http/Resources/Api/Product/ProductCollection.php

Format :

```json
{
    "data": [
        { ProductResource... },
        { ProductResource... }
    ],
    "pagination": {
        "total": 11,
        "count": 11,
        "per_page": 15,
        "current_page": 1,
        "last_page": 1,
        "has_more": false
    }
}
```

---

🏷️ CategoryResource

Fichier : app/Http/Resources/Api/Product/CategoryResource.php

Champs retournés :

```json
{
    "id": 1,
    "name": "Électronique",
    "slug": "electronique",
    "description": "Smartphones, ordinateurs...",
    "image": "http://127.0.0.1:8000/storage/categories/test.jpg",
    "icon": "devices",
    "parent_id": null,
    "parent": null,
    "children": [],
    "subcategories": [
        {
            "id": 1,
            "name": "Smartphones",
            "slug": "smartphones"
        }
    ],
    "products_count": 4,
    "total_products_count": 4,
    "is_active": true,
    "is_featured": true,
    "position": 1
}
```

---

🏪 BrandResource

Fichier : app/Http/Resources/Api/Product/BrandResource.php

Champs retournés :

```json
{
    "id": 1,
    "name": "Samsung",
    "slug": "samsung",
    "description": "Leader mondial de l'électronique",
    "logo": "http://127.0.0.1:8000/storage/brands/test.jpg",
    "banner": null,
    "website": "https://www.samsung.com",
    "email": null,
    "phone": null,
    "country": "Corée du Sud",
    "products_count": 3,
    "average_rating": 3.67,
    "average_rating_formatted": "3.7",
    "is_active": true,
    "is_featured": true
}
```

---

📝 PATTERN COMMUN

Toutes les Resources suivent ce pattern :

```php
class ExampleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // ... champs
        ];
    }

    public function with(Request $request): array
    {
        return [
            'success' => true,
        ];
    }
}
```

```

---

## 📋 RÉSUMÉ

Ce fichier documente :
- ✅ Les 6 Resources
- ✅ Leurs formats JSON
- ✅ Les champs retournés
- ✅ Le pattern commun

---

**Veux-tu le contenu du fichier `07-progress.md` ?** 🚀