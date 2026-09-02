📦 FICHIER : docs/02-database.md (Laravel API)

---

```markdown
# 🗄️ BASE DE DONNÉES - SHOPPRO API

---

## 📋 INFORMATIONS GÉNÉRALES

| Paramètre | Valeur |
|-----------|--------|
| Nom | `shoppro_db` |
| Moteur | MySQL 8.x |
| Charset | utf8mb4 |
| Collation | utf8mb4_unicode_ci |
| Host | 127.0.0.1 |
| Port | 3306 |

---

## 📊 STATISTIQUES

| Élément | Nombre |
|---------|--------|
| Tables | 50+ |
| Migrations | 50 |
| Seeders | 44 |
| Modèles Eloquent | 47 |

---

## 📋 LISTE DES TABLES PAR CATÉGORIE

### 👥 Utilisateurs (4 tables)
| Table | Description |
|-------|-------------|
| `users` | Utilisateurs |
| `user_profiles` | Profils détaillés |
| `user_addresses` | Adresses de livraison |
| `refresh_tokens` | Tokens de rafraîchissement |

### 📦 Catalogue (10 tables)
| Table | Description |
|-------|-------------|
| `categories` | Catégories principales |
| `subcategories` | Sous-catégories |
| `brands` | Marques |
| `products` | Produits |
| `product_images` | Images des produits |
| `product_variants` | Variantes (couleur, taille) |
| `product_attributes` | Attributs |
| `product_specifications` | Spécifications |
| `tags` | Tags |
| `product_tag` | Table pivot produits-tags |

### 🏪 Stock (4 tables)
| Table | Description |
|-------|-------------|
| `warehouses` | Entrepôts |
| `stocks` | Stock par produit/entrepôt |
| `stock_movements` | Mouvements de stock |
| `suppliers` | Fournisseurs |

### 🛒 Commandes (7 tables)
| Table | Description |
|-------|-------------|
| `carts` | Paniers |
| `cart_items` | Éléments du panier |
| `orders` | Commandes |
| `order_items` | Éléments de commande |
| `order_status_histories` | Historique des statuts |
| `shipping_methods` | Méthodes de livraison |
| `order_trackings` | Suivi des commandes |

### 💳 Paiement (4 tables)
| Table | Description |
|-------|-------------|
| `payments` | Paiements |
| `payment_methods` | Méthodes de paiement |
| `transactions` | Transactions |
| `refunds` | Remboursements |

### ⭐ Avis (4 tables)
| Table | Description |
|-------|-------------|
| `favorites` | Favoris |
| `reviews` | Avis clients |
| `review_images` | Images des avis |
| `review_votes` | Votes sur les avis |

### 🎯 Marketing (5 tables)
| Table | Description |
|-------|-------------|
| `coupons` | Codes promo |
| `promotions` | Promotions |
| `flash_sales` | Ventes flash |
| `banners` | Bannières |
| `newsletter_subscribers` | Abonnés newsletter |

### 📞 Support (4 tables)
| Table | Description |
|-------|-------------|
| `notifications` | Notifications |
| `support_tickets` | Tickets de support |
| `ticket_messages` | Messages des tickets |
| `faqs` | FAQ |

### 👑 Admin (5 tables)
| Table | Description |
|-------|-------------|
| `roles` | Rôles |
| `permissions` | Permissions |
| `permission_role` | Pivot permissions-rôles |
| `role_user` | Pivot rôles-utilisateurs |
| `admin_logs` | Journal d'activité |

### ⚙️ Configuration (5 tables)
| Table | Description |
|-------|-------------|
| `settings` | Paramètres généraux |
| `currencies` | Devises |
| `languages` | Langues |
| `countries` | Pays |
| `cities` | Villes |

---

## 🔑 RELATIONS PRINCIPALES

### One-to-One
```

User (1) ──────── (1) UserProfile

```

### One-to-Many
```

User (1) ──────── (N) UserAddress
User (1) ──────── (N) Order
Category (1) ──── (N) Product
Brand (1) ─────── (N) Product
Product (1) ───── (N) ProductImage
Product (1) ───── (N) Review
Cart (1) ──────── (N) CartItem
Order (1) ─────── (N) OrderItem
Order (1) ─────── (N) Payment

```

### Many-to-Many
```

User (N) ──────── (N) Role (via role_user)
Role (N) ──────── (N) Permission (via permission_role)
Product (N) ───── (N) Tag (via product_tag)

```

### Self-Referencing
```

Category (1) ──── (N) Category (parent_id)

```

---

## 📊 DONNÉES SEEDÉES

| Entité | Nombre |
|--------|--------|
| Rôles | 3 (admin, vendeur, client) |
| Permissions | 22 |
| Utilisateurs | 10 |
| Catégories | 8 |
| Sous-catégories | 30 |
| Marques | 9 |
| Produits | 11 |
| Variantes | 15+ |
| Entrepôts | 3 |
| Villes | 12 |
| Coupons | 5 |
| Bannières | 4 |
| FAQ | 9 |

---

## 🔑 INDEX IMPORTANTS

### Table `products`
- `idx_products_category`
- `idx_products_brand`
- `idx_products_active`
- `idx_products_featured`
- `idx_products_rating`
- `idx_products_category_active`
- `idx_products_prices`
- `idx_products_fulltext` (name, description)

### Table `orders`
- `idx_orders_user`
- `idx_orders_status`
- `idx_orders_payment`
- `idx_orders_date`
- `idx_orders_tracking`
- `idx_orders_user_status`

### Table `users`
- `idx_users_email` (unique)

---

## 🗑️ SOFT DELETES

Les tables suivantes ont une colonne `deleted_at` :

`users`, `user_profiles`, `user_addresses`, `categories`, `subcategories`, `brands`, `products`, `product_variants`, `warehouses`, `stocks`, `suppliers`, `carts`, `orders`, `payments`, `refunds`, `reviews`, `coupons`, `promotions`, `flash_sales`, `banners`, `notifications`, `support_tickets`, `faqs`, `roles`, `settings`, `countries`, `cities`
```

---

📋 RÉSUMÉ

Ce fichier documente :

· ✅ Les 50+ tables
· ✅ Les relations
· ✅ Les index
· ✅ Les données seedées
· ✅ Les SoftDeletes

---

Veux-tu le contenu du fichier 03-routes-api.md ? 🚀