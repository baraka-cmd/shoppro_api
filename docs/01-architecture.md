📦 FICHIER : docs/01-architecture.md (Laravel API)

---

```markdown
# 🏗️ ARCHITECTURE - SHOPPRO API (LARAVEL)

---

## 📐 ARCHITECTURE GÉNÉRALE

```

┌─────────────────────────────────────────────────┐
│              ROUTES (routes/api.php)            │
│         Définition des endpoints                │
└──────────────────┬──────────────────────────────┘
↓
┌─────────────────────────────────────────────────┐
│              MIDDLEWARE                         │
│    auth:sanctum, role:admin, role:seller        │
└──────────────────┬──────────────────────────────┘
↓
┌─────────────────────────────────────────────────┐
│              CONTROLLERS (Api/)                 │
│    Auth, Product, Category, Brand               │
└──────────────────┬──────────────────────────────┘
↓
┌─────────────────────────────────────────────────┐
│              REQUESTS (Validation)              │
│    RegisterRequest, LoginRequest, etc.          │
└──────────────────┬──────────────────────────────┘
↓
┌─────────────────────────────────────────────────┐
│              MODELS (Eloquent)                  │
│    User, Product, Category, Brand, etc.         │
└──────────────────┬──────────────────────────────┘
↓
┌─────────────────────────────────────────────────┐
│              RESOURCES (Formatage JSON)         │
│    ProductResource, UserResource, etc.          │
└──────────────────┬──────────────────────────────┘
↓
┌─────────────────────────────────────────────────┐
│              DATABASE (MySQL)                   │
│        50+ tables avec relations                │
└─────────────────────────────────────────────────┘

```

---

## 📂 ORGANISATION DES DOSSIERS

```

app/Http/
├── Controllers/
│   └── Api/
│       ├── Auth/
│       │   └── AuthController.php
│       ├── Product/
│       │   └── ProductController.php
│       ├── Category/
│       │   └── CategoryController.php
│       └── Brand/
│           └── BrandController.php
│
├── Requests/
│   └── Api/
│       └── Auth/
│           ├── RegisterRequest.php
│           ├── LoginRequest.php
│           ├── UpdateProfileRequest.php
│           ├── ChangePasswordRequest.php
│           ├── ForgotPasswordRequest.php
│           └── ResetPasswordRequest.php
│
├── Resources/
│   └── Api/
│       ├── Auth/
│       │   ├── UserResource.php
│       │   └── AuthResource.php
│       └── Product/
│           ├── ProductResource.php
│           ├── ProductCollection.php
│           ├── CategoryResource.php
│           └── BrandResource.php
│
└── Middleware/
├── CheckRole.php
└── CorsMiddleware.php

```

---

## 🔐 MIDDLEWARES

### 1. `auth:sanctum`
- Vérifie le token Sanctum
- Protège les routes privées

### 2. `role:admin,seller`
- Vérifie le rôle de l'utilisateur
- Protège les routes admin/vendeur

### 3. CORS
- Autorise les requêtes cross-origin
- Configuré dans `config/cors.php`

---

## 📝 PATTERN DES CONTRÔLEURS

### Structure standard :

```php
class ProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Logique
            return response()->json([
                'success' => true,
                'message' => 'Message',
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
}
```

---

📝 PATTERN DES REQUESTS

Structure standard :

```php
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est obligatoire',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
```

---

📝 PATTERN DES RESOURCES

Structure standard :

```php
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'main_image' => $this->main_image 
                ? asset('storage/' . $this->main_image) 
                : null,
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

---

📝 FORMAT DE RÉPONSE JSON

Succès :

```json
{
    "success": true,
    "message": "Opération réussie",
    "data": {...}
}
```

Erreur :

```json
{
    "success": false,
    "message": "Erreur",
    "error": "Détails de l'erreur"
}
```

Validation :

```json
{
    "success": false,
    "message": "Erreur de validation",
    "errors": {
        "field": ["Message d'erreur"]
    }
}
```

---

🗄️ RELATIONS ELOQUENT

Relations principales :

```
User (1) ────── (1) UserProfile
User (1) ────── (N) UserAddress
User (N) ────── (N) Role (via role_user)
Role (N) ────── (N) Permission (via permission_role)

Category (1) ── (N) Product
Brand (1) ───── (N) Product
Product (1) ─── (N) ProductImage
Product (1) ─── (N) ProductVariant
Product (1) ─── (N) Review

Cart (1) ────── (N) CartItem
Order (1) ───── (N) OrderItem
Order (1) ───── (N) Payment
Order (1) ───── (N) OrderStatusHistory

User (1) ────── (N) Favorite
User (1) ────── (N) Review
User (1) ────── (N) Order
```

---

🔑 SOFT DELETES

Les modèles suivants utilisent SoftDeletes :

Modèle Table
User users
Product products
Category categories
Brand brands
Order orders
Cart carts
Warehouse warehouses
Stock stocks
Coupon coupons
Promotion promotions
FlashSale flash_sales
Banner banners
Setting settings
Country countries
City cities

```

---

## 📋 RÉSUMÉ

Ce fichier documente :
- ✅ Architecture en couches
- ✅ Organisation des dossiers
- ✅ Middlewares
- ✅ Patterns des contrôleurs/requests/resources
- ✅ Format JSON
- ✅ Relations Eloquent
- ✅ SoftDeletes
```

---

Veux-tu le contenu du fichier 02-database.md ? 🚀