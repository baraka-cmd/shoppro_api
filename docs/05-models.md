📦 FICHIER : docs/05-models.md (Laravel API)

---

```markdown
# 📦 MODÈLES ELOQUENT - SHOPPRO API

---

## 📋 LISTE COMPLÈTE DES MODÈLES (47)

### 👥 User (1)
| Modèle | Table | SoftDeletes |
|--------|-------|-------------|
| `User` | `users` | ✅ |

**Relations :**
- `profile()` → HasOne → UserProfile
- `addresses()` → HasMany → UserAddress
- `roles()` → BelongsToMany → Role
- `orders()` → HasMany → Order
- `favorites()` → HasMany → Favorite
- `reviews()` → HasMany → Review
- `notifications()` → HasMany → Notification

**Méthodes :**
- `hasRole()` - Vérifier rôle
- `isAdmin()` / `isSeller()` / `isClient()`
- `createAuthToken()` - Créer token Sanctum
- `revokeAllTokens()` - Révoquer tokens

---

### 👥 User/ (4)
| Modèle | Table | SoftDeletes |
|--------|-------|-------------|
| `UserProfile` | `user_profiles` | ✅ |
| `UserAddress` | `user_addresses` | ✅ |
| `Role` | `roles` | ✅ |
| `Permission` | `permissions` | ❌ |

**Relations UserProfile :**
- `user()` → BelongsTo → User

**Relations UserAddress :**
- `user()` → BelongsTo → User
- `orders()` → HasMany → Order

**Relations Role :**
- `users()` → BelongsToMany → User
- `permissions()` → BelongsToMany → Permission

**Relations Permission :**
- `roles()` → BelongsToMany → Role

---

### 📦 Catalog/ (12)
| Modèle | Table | SoftDeletes |
|--------|-------|-------------|
| `Category` | `categories` | ✅ |
| `Subcategory` | `subcategories` | ✅ |
| `Brand` | `brands` | ✅ |
| `Product` | `products` | ✅ |
| `ProductImage` | `product_images` | ❌ |
| `ProductVariant` | `product_variants` | ✅ |
| `ProductAttribute` | `product_attributes` | ❌ |
| `ProductSpecification` | `product_specifications` | ❌ |
| `Tag` | `tags` | ❌ |
| `Warehouse` | `warehouses` | ✅ |
| `Stock` | `stocks` | ✅ |
| `StockMovement` | `stock_movements` | ❌ |

**Relations Category :**
- `parent()` → BelongsTo → Category (self)
- `children()` → HasMany → Category (self)
- `subcategories()` → HasMany → Subcategory
- `products()` → HasMany → Product

**Relations Product :**
- `category()` → BelongsTo → Category
- `subcategory()` → BelongsTo → Subcategory
- `brand()` → BelongsTo → Brand
- `images()` → HasMany → ProductImage
- `variants()` → HasMany → ProductVariant
- `attributes()` → HasMany → ProductAttribute
- `specifications()` → HasMany → ProductSpecification
- `tags()` → BelongsToMany → Tag
- `stocks()` → HasMany → Stock
- `reviews()` → HasMany → Review
- `favorites()` → HasMany → Favorite

**Accesseurs Product :**
- `currentPrice` - Prix actuel
- `discountPercentage` - % réduction
- `mainImageUrl` - URL image complète
- `isInStock` - En stock ?
- `isAvailable` - Disponible ?

---

### 🛒 Order/ (8)
| Modèle | Table | SoftDeletes |
|--------|-------|-------------|
| `Cart` | `carts` | ✅ |
| `CartItem` | `cart_items` | ❌ |
| `Order` | `orders` | ✅ |
| `OrderItem` | `order_items` | ❌ |
| `OrderStatusHistory` | `order_status_histories` | ❌ |
| `ShippingMethod` | `shipping_methods` | ✅ |
| `OrderTracking` | `order_trackings` | ❌ |
| `Supplier` | `suppliers` | ✅ |

**Relations Order :**
- `user()` → BelongsTo → User
- `shippingAddress()` → BelongsTo → UserAddress
- `billingAddress()` → BelongsTo → UserAddress
- `items()` → HasMany → OrderItem
- `statusHistory()` → HasMany → OrderStatusHistory
- `trackings()` → HasMany → OrderTracking
- `payments()` → HasMany → Payment

**Méthodes Order :**
- `generateOrderNumber()` - CMD-YYYYMMDD-XXXX
- `updateStatus()` - Changer statut + historique
- `cancel()` - Annuler

---

### 💳 Payment/ (4)
| Modèle | Table | SoftDeletes |
|--------|-------|-------------|
| `Payment` | `payments` | ✅ |
| `PaymentMethod` | `payment_methods` | ✅ |
| `Transaction` | `transactions` | ❌ |
| `Refund` | `refunds` | ❌ |

**Méthodes Payment :**
- `generatePaymentNumber()` - PAY-YYYYMMDD-XXXX
- `markAsCompleted()` - Paiement réussi
- `markAsFailed()` - Paiement échoué
- `markAsRefunded()` - Remboursé

---

### ⭐ Review/ (4)
| Modèle | Table | SoftDeletes |
|--------|-------|-------------|
| `Favorite` | `favorites` | ❌ |
| `Review` | `reviews` | ✅ |
| `ReviewImage` | `review_images` | ❌ |
| `ReviewVote` | `review_votes` | ❌ |

**Méthodes Review :**
- `approve()` - Approuver
- `reject()` - Rejeter
- `respond()` - Répondre (admin)

---

### 🎯 Marketing/ (4)
| Modèle | Table | SoftDeletes |
|--------|-------|-------------|
| `Coupon` | `coupons` | ✅ |
| `Promotion` | `promotions` | ✅ |
| `FlashSale` | `flash_sales` | ✅ |
| `Banner` | `banners` | ✅ |

**Méthodes Coupon :**
- `calculateDiscount()` - Calculer réduction
- `isValid` - Vérifier validité

---

### 📞 Support/ (4)
| Modèle | Table | SoftDeletes |
|--------|-------|-------------|
| `Notification` | `notifications` | ✅ |
| `SupportTicket` | `support_tickets` | ✅ |
| `TicketMessage` | `ticket_messages` | ❌ |
| `Faq` | `faqs` | ✅ |

---

### 👑 Admin/ (4)
| Modèle | Table | SoftDeletes |
|--------|-------|-------------|
| `AdminLog` | `admin_logs` | ❌ |
| `Setting` | `settings` | ❌ |
| `NewsletterSubscriber` | `newsletter_subscribers` | ❌ |
| `Currency` | `currencies` | ❌ |

**Méthodes Setting :**
- `get()` - Récupérer valeur
- `set()` - Définir valeur
- `getAllGrouped()` - Tous groupés

---

### ⚙️ Config/ (3)
| Modèle | Table | SoftDeletes |
|--------|-------|-------------|
| `Language` | `languages` | ❌ |
| `Country` | `countries` | ✅ |
| `City` | `cities` | ✅ |

---

### 🔑 Modèles additionnels
| Modèle | Table | Rôle |
|--------|-------|------|
| `RefreshToken` | `refresh_tokens` | Refresh tokens custom |

**Méthodes RefreshToken :**
- `generateToken()` - Token unique
- `isValid()` - Valide ?
- `revoke()` - Révoquer
- `markAsUsed()` - Marquer utilisé

---

## 📊 STATISTIQUES

| Catégorie | Nombre | SoftDeletes |
|-----------|--------|-------------|
| User | 5 | 4 |
| Catalog | 12 | 7 |
| Order | 8 | 3 |
| Payment | 4 | 2 |
| Review | 4 | 1 |
| Marketing | 4 | 4 |
| Support | 4 | 3 |
| Admin | 4 | 0 |
| Config | 3 | 2 |
| **TOTAL** | **48** | **26** |
```

---

📋 RÉSUMÉ

Ce fichier documente :

· ✅ Les 48 modèles
· ✅ Leurs tables
· ✅ Leurs relations
· ✅ Leurs méthodes
· ✅ Leurs SoftDeletes

---

Veux-tu le contenu du fichier 06-resources.md ? 🚀