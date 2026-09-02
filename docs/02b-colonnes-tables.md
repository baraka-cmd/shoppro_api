📦 FICHIER : docs/02b-colonnes-tables.md COMPLET

---

```markdown
# 🗄️ COLONNES DÉTAILLÉES DE TOUTES LES TABLES - SHOPPRO

---

## 👥 TABLE : `users`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY, AUTO_INCREMENT |
| name | varchar(255) | NOT NULL |
| email | varchar(255) | UNIQUE, NOT NULL |
| email_verified_at | timestamp | NULLABLE |
| password | varchar(255) | NOT NULL |
| remember_token | varchar(100) | NULLABLE |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| deleted_at | timestamp | NULLABLE (SoftDeletes) |

---

## 👥 TABLE : `user_profiles`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| user_id | bigint | FOREIGN KEY → users, CASCADE |
| first_name | varchar(100) | NULLABLE |
| last_name | varchar(100) | NULLABLE |
| phone | varchar(20) | NULLABLE |
| avatar | varchar(255) | NULLABLE |
| birth_date | date | NULLABLE |
| gender | enum(male,female,other) | NULLABLE |
| country | varchar(100) | NULLABLE |
| city | varchar(100) | NULLABLE |
| address | text | NULLABLE |
| language | varchar(10) | DEFAULT 'fr' |
| currency | varchar(10) | DEFAULT 'FCFA' |
| newsletter_subscribed | boolean | DEFAULT true |
| sms_notifications | boolean | DEFAULT true |
| push_notifications | boolean | DEFAULT true |
| total_orders | int | DEFAULT 0 |
| total_spent | decimal(12,2) | DEFAULT 0 |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| deleted_at | timestamp | NULLABLE |

---

## 👥 TABLE : `user_addresses`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| user_id | bigint | FOREIGN KEY → users, CASCADE |
| full_name | varchar(150) | NOT NULL |
| phone | varchar(20) | NOT NULL |
| email | varchar(150) | NULLABLE |
| address_line1 | varchar(255) | NOT NULL |
| address_line2 | varchar(255) | NULLABLE |
| city | varchar(100) | NOT NULL |
| state | varchar(100) | NULLABLE |
| postal_code | varchar(20) | NULLABLE |
| country | varchar(100) | DEFAULT 'Cameroun' |
| type | enum(home,work,other) | DEFAULT 'home' |
| is_default | boolean | DEFAULT false |
| latitude | decimal(10,8) | NULLABLE |
| longitude | decimal(11,8) | NULLABLE |
| delivery_instructions | text | NULLABLE |
| is_active | boolean | DEFAULT true |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| deleted_at | timestamp | NULLABLE |

---

## 👥 TABLE : `roles`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| name | varchar(100) | UNIQUE |
| display_name | varchar(150) | NOT NULL |
| description | text | NULLABLE |
| level | int | DEFAULT 1 |
| color | varchar(20) | NULLABLE |
| icon | varchar(100) | NULLABLE |
| is_active | boolean | DEFAULT true |
| is_system | boolean | DEFAULT false |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| deleted_at | timestamp | NULLABLE |

---

## 👥 TABLE : `permissions`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| name | varchar(100) | UNIQUE |
| display_name | varchar(150) | NOT NULL |
| description | text | NULLABLE |
| group | varchar(100) | NULLABLE |
| action | varchar(50) | NULLABLE |
| is_active | boolean | DEFAULT true |
| is_system | boolean | DEFAULT false |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |

---

## 👥 TABLE : `permission_role` (PIVOT)

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| permission_id | bigint | FOREIGN KEY → permissions, CASCADE |
| role_id | bigint | FOREIGN KEY → roles, CASCADE |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| | | UNIQUE(permission_id, role_id) |

---

## 👥 TABLE : `role_user` (PIVOT)

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| role_id | bigint | FOREIGN KEY → roles, CASCADE |
| user_id | bigint | FOREIGN KEY → users, CASCADE |
| assigned_at | timestamp | DEFAULT CURRENT_TIMESTAMP |
| assigned_by | bigint | FOREIGN KEY → users, NULLABLE |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| | | UNIQUE(role_id, user_id) |

---

## 🔑 TABLE : `refresh_tokens`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| user_id | bigint | FOREIGN KEY → users, CASCADE |
| token | varchar(500) | UNIQUE |
| device_name | varchar(150) | NULLABLE |
| device_type | varchar(50) | NULLABLE |
| ip_address | varchar(45) | NULLABLE |
| user_agent | text | NULLABLE |
| expires_at | timestamp | NOT NULL |
| last_used_at | timestamp | NULLABLE |
| revoked_at | timestamp | NULLABLE |
| is_revoked | boolean | DEFAULT false |
| is_active | boolean | DEFAULT true |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |

---

## 📦 TABLE : `categories`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| name | varchar(150) | NOT NULL |
| slug | varchar(180) | UNIQUE |
| description | text | NULLABLE |
| image | varchar(255) | NULLABLE |
| icon | varchar(100) | NULLABLE |
| parent_id | bigint | FOREIGN KEY → categories, SET NULL |
| position | int | DEFAULT 0 |
| is_active | boolean | DEFAULT true |
| is_featured | boolean | DEFAULT false |
| meta_title | varchar(200) | NULLABLE |
| meta_description | text | NULLABLE |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| deleted_at | timestamp | NULLABLE |

---

## 📦 TABLE : `subcategories`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| category_id | bigint | FOREIGN KEY → categories, CASCADE |
| name | varchar(150) | NOT NULL |
| slug | varchar(180) | UNIQUE |
| description | text | NULLABLE |
| image | varchar(255) | NULLABLE |
| icon | varchar(100) | NULLABLE |
| position | int | DEFAULT 0 |
| is_active | boolean | DEFAULT true |
| meta_title | varchar(200) | NULLABLE |
| meta_description | text | NULLABLE |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| deleted_at | timestamp | NULLABLE |

---

## 📦 TABLE : `brands`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| name | varchar(150) | NOT NULL |
| slug | varchar(180) | UNIQUE |
| description | text | NULLABLE |
| logo | varchar(255) | NULLABLE |
| banner | varchar(255) | NULLABLE |
| website | varchar(255) | NULLABLE |
| email | varchar(150) | NULLABLE |
| phone | varchar(20) | NULLABLE |
| country | varchar(100) | NULLABLE |
| products_count | int | DEFAULT 0 |
| average_rating | decimal(3,2) | DEFAULT 0 |
| is_active | boolean | DEFAULT true |
| is_featured | boolean | DEFAULT false |
| meta_title | varchar(200) | NULLABLE |
| meta_description | text | NULLABLE |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| deleted_at | timestamp | NULLABLE |

---

## 📦 TABLE : `products`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| name | varchar(255) | NOT NULL |
| slug | varchar(280) | UNIQUE |
| short_description | text | NULLABLE |
| description | longtext | NULLABLE |
| price | decimal(12,2) | NOT NULL |
| discount_price | decimal(12,2) | NULLABLE |
| cost_price | decimal(12,2) | NULLABLE |
| tax_rate | decimal(5,2) | DEFAULT 0 |
| stock | int | DEFAULT 0 |
| min_stock | int | DEFAULT 5 |
| max_stock | int | NULLABLE |
| sku | varchar(100) | UNIQUE |
| barcode | varchar(100) | NULLABLE |
| category_id | bigint | FOREIGN KEY → categories, CASCADE |
| subcategory_id | bigint | FOREIGN KEY → subcategories, SET NULL |
| brand_id | bigint | FOREIGN KEY → brands, SET NULL |
| main_image | varchar(255) | NULLABLE |
| views_count | int | DEFAULT 0 |
| sales_count | int | DEFAULT 0 |
| favorites_count | int | DEFAULT 0 |
| average_rating | decimal(3,2) | DEFAULT 0 |
| reviews_count | int | DEFAULT 0 |
| is_active | boolean | DEFAULT true |
| is_featured | boolean | DEFAULT false |
| is_new | boolean | DEFAULT false |
| is_bestseller | boolean | DEFAULT false |
| is_on_sale | boolean | DEFAULT false |
| is_digital | boolean | DEFAULT false |
| available_from | date | NULLABLE |
| available_until | date | NULLABLE |
| meta_title | varchar(200) | NULLABLE |
| meta_description | text | NULLABLE |
| meta_keywords | varchar(255) | NULLABLE |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| deleted_at | timestamp | NULLABLE |

---

## 📦 TABLE : `product_images`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| product_id | bigint | FOREIGN KEY → products, CASCADE |
| image_path | varchar(255) | NOT NULL |
| thumbnail_path | varchar(255) | NULLABLE |
| alt_text | varchar(255) | NULLABLE |
| caption | varchar(255) | NULLABLE |
| position | int | DEFAULT 0 |
| is_primary | boolean | DEFAULT false |
| width | int | NULLABLE |
| height | int | NULLABLE |
| file_size | int | NULLABLE |
| type | enum(image,video,gif) | DEFAULT 'image' |
| is_active | boolean | DEFAULT true |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |

---

## 📦 TABLE : `product_variants`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| product_id | bigint | FOREIGN KEY → products, CASCADE |
| sku | varchar(100) | UNIQUE |
| barcode | varchar(100) | NULLABLE |
| name | varchar(255) | NOT NULL |
| color | varchar(50) | NULLABLE |
| color_code | varchar(20) | NULLABLE |
| size | varchar(30) | NULLABLE |
| weight | varchar(50) | NULLABLE |
| material | varchar(100) | NULLABLE |
| price | decimal(12,2) | NULLABLE |
| discount_price | decimal(12,2) | NULLABLE |
| stock | int | DEFAULT 0 |
| min_stock | int | DEFAULT 5 |
| image | varchar(255) | NULLABLE |
| is_active | boolean | DEFAULT true |
| is_default | boolean | DEFAULT false |
| position | int | DEFAULT 0 |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| deleted_at | timestamp | NULLABLE |

---

## 📦 TABLE : `product_attributes`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| product_id | bigint | FOREIGN KEY → products, CASCADE |
| name | varchar(100) | NOT NULL |
| value | varchar(255) | NOT NULL |
| unit | varchar(50) | NULLABLE |
| icon | varchar(100) | NULLABLE |
| color_code | varchar(20) | NULLABLE |
| position | int | DEFAULT 0 |
| is_visible | boolean | DEFAULT true |
| is_filterable | boolean | DEFAULT false |
| is_active | boolean | DEFAULT true |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |

---

## 📦 TABLE : `product_specifications`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| product_id | bigint | FOREIGN KEY → products, CASCADE |
| label | varchar(150) | NOT NULL |
| value | varchar(255) | NOT NULL |
| group | varchar(100) | NULLABLE |
| position | int | DEFAULT 0 |
| icon | varchar(100) | NULLABLE |
| is_highlighted | boolean | DEFAULT false |
| is_active | boolean | DEFAULT true |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |

---

## 📦 TABLE : `tags`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| name | varchar(100) | NOT NULL |
| slug | varchar(120) | UNIQUE |
| color | varchar(20) | NULLABLE |
| background_color | varchar(20) | NULLABLE |
| icon | varchar(100) | NULLABLE |
| description | text | NULLABLE |
| products_count | int | DEFAULT 0 |
| is_active | boolean | DEFAULT true |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |

---

## 📦 TABLE : `product_tag` (PIVOT)

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| product_id | bigint | FOREIGN KEY → products, CASCADE |
| tag_id | bigint | FOREIGN KEY → tags, CASCADE |
| position | int | DEFAULT 0 |
| assigned_at | timestamp | DEFAULT CURRENT_TIMESTAMP |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| | | UNIQUE(product_id, tag_id) |

---

## 🏪 TABLE : `warehouses`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| name | varchar(150) | NOT NULL |
| code | varchar(50) | UNIQUE |
| description | text | NULLABLE |
| address_line1 | varchar(255) | NOT NULL |
| address_line2 | varchar(255) | NULLABLE |
| city | varchar(100) | NOT NULL |
| state | varchar(100) | NULLABLE |
| postal_code | varchar(20) | NULLABLE |
| country | varchar(100) | DEFAULT 'Cameroun' |
| latitude | decimal(10,8) | NULLABLE |
| longitude | decimal(11,8) | NULLABLE |
| phone | varchar(20) | NULLABLE |
| email | varchar(150) | NULLABLE |
| manager_name | varchar(150) | NULLABLE |
| capacity | int | NULLABLE |
| current_occupancy | int | DEFAULT 0 |
| unit | varchar(20) | DEFAULT 'm2' |
| is_active | boolean | DEFAULT true |
| is_default | boolean | DEFAULT false |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| deleted_at | timestamp | NULLABLE |

---

## 🏪 TABLE : `stocks`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| product_id | bigint | FOREIGN KEY → products, CASCADE |
| warehouse_id | bigint | FOREIGN KEY → warehouses, CASCADE |
| product_variant_id | bigint | FOREIGN KEY → product_variants, CASCADE |
| quantity | int | DEFAULT 0 |
| reserved_quantity | int | DEFAULT 0 |
| available_quantity | int | DEFAULT 0 |
| min_quantity | int | DEFAULT 5 |
| max_quantity | int | NULLABLE |
| location | varchar(100) | NULLABLE |
| shelf | varchar(50) | NULLABLE |
| bin | varchar(50) | NULLABLE |
| unit_cost | decimal(12,2) | NULLABLE |
| total_value | decimal(12,2) | NULLABLE |
| last_counted_at | date | NULLABLE |
| expiry_date | date | NULLABLE |
| is_active | boolean | DEFAULT true |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| deleted_at | timestamp | NULLABLE |
| | | UNIQUE(product_id, warehouse_id, product_variant_id) |

---

## 🏪 TABLE : `stock_movements`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| product_id | bigint | FOREIGN KEY → products, CASCADE |
| product_variant_id | bigint | FOREIGN KEY → product_variants, CASCADE |
| warehouse_id | bigint | FOREIGN KEY → warehouses, CASCADE |
| user_id | bigint | FOREIGN KEY → users, SET NULL |
| type | enum(in,out,adjustment,transfer,return,sale,purchase) | NOT NULL |
| quantity | int | NOT NULL |
| quantity_before | int | DEFAULT 0 |
| quantity_after | int | DEFAULT 0 |
| unit_price | decimal(12,2) | NULLABLE |
| total_price | decimal(12,2) | NULLABLE |
| reference | varchar(100) | NULLABLE |
| note | text | NULLABLE |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |

---

## 🏪 TABLE : `suppliers`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| name | varchar(150) | NOT NULL |
| code | varchar(50) | UNIQUE |
| description | text | NULLABLE |
| contact_name | varchar(150) | NULLABLE |
| email | varchar(150) | NULLABLE |
| phone | varchar(20) | NULLABLE |
| phone_alt | varchar(20) | NULLABLE |
| address_line1 | varchar(255) | NULLABLE |
| address_line2 | varchar(255) | NULLABLE |
| city | varchar(100) | NULLABLE |
| state | varchar(100) | NULLABLE |
| country | varchar(100) | DEFAULT 'Cameroun' |
| bank_name | varchar(150) | NULLABLE |
| account_number | varchar(50) | NULLABLE |
| account_name | varchar(150) | NULLABLE |
| products_count | int | DEFAULT 0 |
| total_purchases | decimal(15,2) | DEFAULT 0 |
| orders_count | int | DEFAULT 0 |
| last_order_date | date | NULLABLE |
| payment_terms | int | DEFAULT 30 |
| delivery_time | int | DEFAULT 7 |
| minimum_order | decimal(12,2) | DEFAULT 0 |
| is_active | boolean | DEFAULT true |
| is_preferred | boolean | DEFAULT false |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| deleted_at | timestamp | NULLABLE |

---

## 🛒 TABLE : `carts`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| user_id | bigint | FOREIGN KEY → users, CASCADE, NULLABLE |
| session_id | varchar(100) | NULLABLE |
| subtotal | decimal(12,2) | DEFAULT 0 |
| discount | decimal(12,2) | DEFAULT 0 |
| tax | decimal(12,2) | DEFAULT 0 |
| shipping_fee | decimal(12,2) | DEFAULT 0 |
| total | decimal(12,2) | DEFAULT 0 |
| coupon_code | varchar(50) | NULLABLE |
| coupon_discount | decimal(12,2) | DEFAULT 0 |
| items_count | int | DEFAULT 0 |
| total_items | int | DEFAULT 0 |
| currency | varchar(10) | DEFAULT 'FCFA' |
| last_activity_at | timestamp | NULLABLE |
| abandoned_at | timestamp | NULLABLE |
| is_active | boolean | DEFAULT true |
| is_abandoned | boolean | DEFAULT false |
| is_converted | boolean | DEFAULT false |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| deleted_at | timestamp | NULLABLE |

---

## 🛒 TABLE : `cart_items`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| cart_id | bigint | FOREIGN KEY → carts, CASCADE |
| product_id | bigint | FOREIGN KEY → products, CASCADE |
| product_variant_id | bigint | FOREIGN KEY → product_variants, CASCADE |
| quantity | int | DEFAULT 1 |
| unit_price | decimal(12,2) | NOT NULL |
| discount_price | decimal(12,2) | NULLABLE |
| total_price | decimal(12,2) | NOT NULL |
| product_name | varchar(255) | NULLABLE |
| product_image | varchar(255) | NULLABLE |
| product_sku | varchar(100) | NULLABLE |
| variant_name | varchar(255) | NULLABLE |
| variant_color | varchar(50) | NULLABLE |
| variant_size | varchar(30) | NULLABLE |
| note | text | NULLABLE |
| is_active | boolean | DEFAULT true |
| is_saved_for_later | boolean | DEFAULT false |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| | | UNIQUE(cart_id, product_id, product_variant_id) |

---

## 🛒 TABLE : `orders`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| order_number | varchar(50) | UNIQUE |
| user_id | bigint | FOREIGN KEY → users, CASCADE |
| shipping_address_id | bigint | FOREIGN KEY → user_addresses, SET NULL |
| billing_address_id | bigint | FOREIGN KEY → user_addresses, SET NULL |
| subtotal | decimal(12,2) | NOT NULL |
| discount | decimal(12,2) | DEFAULT 0 |
| coupon_discount | decimal(12,2) | DEFAULT 0 |
| shipping_fee | decimal(12,2) | DEFAULT 0 |
| tax | decimal(12,2) | DEFAULT 0 |
| total | decimal(12,2) | NOT NULL |
| status | enum(pending,confirmed,processing,shipped,delivered,cancelled,refunded,returned) | DEFAULT 'pending' |
| payment_status | enum(pending,paid,failed,refunded,partially_refunded) | DEFAULT 'pending' |
| shipping_method | varchar(100) | NULLABLE |
| tracking_number | varchar(100) | NULLABLE |
| estimated_delivery_date | date | NULLABLE |
| shipped_at | timestamp | NULLABLE |
| delivered_at | timestamp | NULLABLE |
| coupon_code | varchar(50) | NULLABLE |
| customer_note | text | NULLABLE |
| admin_note | text | NULLABLE |
| currency | varchar(10) | DEFAULT 'FCFA' |
| cancelled_at | timestamp | NULLABLE |
| cancellation_reason | varchar(255) | NULLABLE |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| deleted_at | timestamp | NULLABLE |

---

## 🛒 TABLE : `order_items`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| order_id | bigint | FOREIGN KEY → orders, CASCADE |
| product_id | bigint | FOREIGN KEY → products, CASCADE |
| product_variant_id | bigint | FOREIGN KEY → product_variants, SET NULL |
| quantity | int | NOT NULL |
| unit_price | decimal(12,2) | NOT NULL |
| discount_price | decimal(12,2) | NULLABLE |
| total_price | decimal(12,2) | NOT NULL |
| product_name | varchar(255) | NOT NULL |
| product_image | varchar(255) | NULLABLE |
| product_sku | varchar(100) | NOT NULL |
| product_barcode | varchar(100) | NULLABLE |
| variant_name | varchar(255) | NULLABLE |
| variant_color | varchar(50) | NULLABLE |
| variant_size | varchar(30) | NULLABLE |
| item_status | enum(pending,shipped,delivered,cancelled,returned) | DEFAULT 'pending' |
| is_returned | boolean | DEFAULT false |
| return_date | date | NULLABLE |
| return_reason | varchar(255) | NULLABLE |
| is_refunded | boolean | DEFAULT false |
| refund_amount | decimal(12,2) | NULLABLE |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |

---

## 🛒 TABLE : `order_status_histories`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| order_id | bigint | FOREIGN KEY → orders, CASCADE |
| user_id | bigint | FOREIGN KEY → users, SET NULL |
| old_status | enum(pending,confirmed,processing,shipped,delivered,cancelled,refunded,returned) | NULLABLE |
| new_status | enum(pending,confirmed,processing,shipped,delivered,cancelled,refunded,returned) | NOT NULL |
| note | text | NULLABLE |
| reason | varchar(255) | NULLABLE |
| location | varchar(255) | NULLABLE |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |

---

## 🛒 TABLE : `shipping_methods`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| name | varchar(150) | NOT NULL |
| code | varchar(50) | UNIQUE |
| description | text | NULLABLE |
| base_cost | decimal(12,2) | DEFAULT 0 |
| cost_per_kg | decimal(12,2) | DEFAULT 0 |
| free_shipping_threshold | decimal(12,2) | NULLABLE |
| min_delivery_days | int | DEFAULT 1 |
| max_delivery_days | int | DEFAULT 7 |
| countries | varchar(255) | NULLABLE |
| cities | varchar(255) | NULLABLE |
| excluded_areas | varchar(255) | NULLABLE |
| max_weight | decimal(10,2) | NULLABLE |
| max_dimensions | decimal(10,2) | NULLABLE |
| icon | varchar(100) | NULLABLE |
| position | int | DEFAULT 0 |
| is_active | boolean | DEFAULT true |
| is_default | boolean | DEFAULT false |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| deleted_at | timestamp | NULLABLE |

---

## 🛒 TABLE : `order_trackings`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| order_id | bigint | FOREIGN KEY → orders, CASCADE |
| tracking_number | varchar(100) | NOT NULL |
| carrier | varchar(100) | NULLABLE |
| carrier_url | varchar(255) | NULLABLE |
| status | enum(pending,picked_up,in_transit,out_for_delivery,delivered,failed,returned) | DEFAULT 'pending' |
| current_location | varchar(255) | NULLABLE |
| latitude | decimal(10,8) | NULLABLE |
| longitude | decimal(11,8) | NULLABLE |
| estimated_delivery | timestamp | NULLABLE |
| actual_delivery | timestamp | NULLABLE |
| recipient_name | varchar(150) | NULLABLE |
| recipient_phone | varchar(20) | NULLABLE |
| events | text | NULLABLE (JSON) |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |

---

## 💳 TABLE : `payments`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| payment_number | varchar(50) | UNIQUE |
| order_id | bigint | FOREIGN KEY → orders, CASCADE |
| user_id | bigint | FOREIGN KEY → users, CASCADE |
| amount | decimal(12,2) | NOT NULL |
| fee | decimal(12,2) | DEFAULT 0 |
| total_amount | decimal(12,2) | NOT NULL |
| currency | varchar(10) | DEFAULT 'FCFA' |
| method | enum(card,paypal,mobile_money,bank_transfer,cash_on_delivery,wallet,points) | NOT NULL |
| status | enum(pending,processing,completed,failed,cancelled,refunded,partially_refunded) | DEFAULT 'pending' |
| transaction_id | varchar(150) | NULLABLE |
| gateway_reference | varchar(150) | NULLABLE |
| authorization_code | varchar(100) | NULLABLE |
| response_data | text | NULLABLE (JSON) |
| error_message | text | NULLABLE |
| paid_at | timestamp | NULLABLE |
| failed_at | timestamp | NULLABLE |
| refunded_at | timestamp | NULLABLE |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| deleted_at | timestamp | NULLABLE |

---

## 💳 TABLE : `payment_methods`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| name | varchar(150) | NOT NULL |
| code | varchar(50) | UNIQUE |
| description | text | NULLABLE |
| icon | varchar(100) | NULLABLE |
| logo | varchar(255) | NULLABLE |
| transaction_fee | decimal(5,2) | DEFAULT 0 |
| fixed_fee | decimal(12,2) | DEFAULT 0 |
| minimum_amount | decimal(12,2) | NULLABLE |
| maximum_amount | decimal(12,2) | NULLABLE |
| countries | varchar(255) | NULLABLE |
| currencies | varchar(255) | NULLABLE |
| api_key | text | NULLABLE |
| api_secret | text | NULLABLE |
| webhook_url | text | NULLABLE |
| position | int | DEFAULT 0 |
| is_active | boolean | DEFAULT true |
| is_default | boolean | DEFAULT false |
| is_test_mode | boolean | DEFAULT false |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| deleted_at | timestamp | NULLABLE |

---

## 💳 TABLE : `transactions`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| transaction_number | varchar(50) | UNIQUE |
| user_id | bigint | FOREIGN KEY → users, CASCADE |
| order_id | bigint | FOREIGN KEY → orders, SET NULL |
| amount | decimal(12,2) | NOT NULL |
| fee | decimal(12,2) | DEFAULT 0 |
| net_amount | decimal(12,2) | NOT NULL |
| currency | varchar(10) | DEFAULT 'FCFA' |
| type | enum(payment,refund,wallet_topup,withdrawal,transfer,commission) | NOT NULL |
| status | enum(pending,processing,completed,failed,cancelled) | DEFAULT 'pending' |
| payment_method | varchar(100) | NULLABLE |
| payment_gateway | varchar(100) | NULLABLE |
| gateway_transaction_id | varchar(150) | NULLABLE |
| gateway_reference | varchar(150) | NULLABLE |
| description | text | NULLABLE |
| metadata | text | NULLABLE (JSON) |
| error_message | text | NULLABLE |
| completed_at | timestamp | NULLABLE |
| failed_at | timestamp | NULLABLE |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |

---

## 💳 TABLE : `refunds`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| refund_number | varchar(50) | UNIQUE |
| payment_id | bigint | FOREIGN KEY → payments, CASCADE |
| order_id | bigint | FOREIGN KEY → orders, CASCADE |
| user_id | bigint | FOREIGN KEY → users, CASCADE |
| amount | decimal(12,2) | NOT NULL |
| fee_refunded | decimal(12,2) | DEFAULT 0 |
| total_refunded | decimal(12,2) | NOT NULL |
| type | enum(full,partial,shipping_only) | NOT NULL |
| status | enum(pending,processing,completed,failed,cancelled) | DEFAULT 'pending' |
| reason | varchar(255) | NOT NULL |
| note | text | NULLABLE |
| gateway_refund_id | varchar(150) | NULLABLE |
| processed_at | timestamp | NULLABLE |
| completed_at | timestamp | NULLABLE |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |

---

## ⭐ TABLE : `favorites`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| user_id | bigint | FOREIGN KEY → users, CASCADE |
| product_id | bigint | FOREIGN KEY → products, CASCADE |
| note | varchar(255) | NULLABLE |
| collection_name | varchar(100) | NULLABLE |
| price_alert | boolean | DEFAULT false |
| price_alert_threshold | decimal(12,2) | NULLABLE |
| added_at | timestamp | DEFAULT CURRENT_TIMESTAMP |
| is_active | boolean | DEFAULT true |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| | | UNIQUE(user_id, product_id) |

---

## ⭐ TABLE : `reviews`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| user_id | bigint | FOREIGN KEY → users, CASCADE |
| product_id | bigint | FOREIGN KEY → products, CASCADE |
| order_id | bigint | FOREIGN KEY → orders, SET NULL |
| rating | int | NOT NULL (1-5) |
| quality_rating | int | NULLABLE (1-5) |
| price_rating | int | NULLABLE (1-5) |
| shipping_rating | int | NULLABLE (1-5) |
| title | varchar(255) | NULLABLE |
| content | text | NOT NULL |
| is_verified_purchase | boolean | DEFAULT false |
| is_recommended | boolean | DEFAULT true |
| helpful_count | int | DEFAULT 0 |
| not_helpful_count | int | DEFAULT 0 |
| views_count | int | DEFAULT 0 |
| status | enum(pending,approved,rejected,spam) | DEFAULT 'pending' |
| admin_response | text | NULLABLE |
| admin_response_at | timestamp | NULLABLE |
| approved_at | timestamp | NULLABLE |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| deleted_at | timestamp | NULLABLE |
| | | UNIQUE(user_id, product_id) |

---

## ⭐ TABLE : `review_images`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| review_id | bigint | FOREIGN KEY → reviews, CASCADE |
| image_path | varchar(255) | NOT NULL |
| thumbnail_path | varchar(255) | NULLABLE |
| alt_text | varchar(255) | NULLABLE |
| caption | varchar(255) | NULLABLE |
| width | int | NULLABLE |
| height | int | NULLABLE |
| file_size | int | NULLABLE |
| type | enum(image,video) | DEFAULT 'image' |
| position | int | DEFAULT 0 |
| is_active | boolean | DEFAULT true |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |

---

## ⭐ TABLE : `review_votes`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| review_id | bigint | FOREIGN KEY → reviews, CASCADE |
| user_id | bigint | FOREIGN KEY → users, CASCADE |
| vote_type | enum(helpful,not_helpful) | NOT NULL |
| voted_at | timestamp | DEFAULT CURRENT_TIMESTAMP |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| | | UNIQUE(review_id, user_id) |

---

## 🎯 TABLE : `coupons`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| code | varchar(50) | UNIQUE |
| name | varchar(150) | NOT NULL |
| description | text | NULLABLE |
| discount_type | enum(percentage,fixed,free_shipping) | NOT NULL |
| discount_value | decimal(12,2) | NOT NULL |
| maximum_discount | decimal(12,2) | NULLABLE |
| minimum_order | decimal(12,2) | DEFAULT 0 |
| usage_limit | int | NULLABLE |
| usage_per_user | int | DEFAULT 1 |
| usage_count | int | DEFAULT 0 |
| start_date | date | NOT NULL |
| end_date | date | NOT NULL |
| categories | varchar(255) | NULLABLE (JSON) |
| products | varchar(255) | NULLABLE (JSON) |
| users | varchar(255) | NULLABLE (JSON) |
| is_active | boolean | DEFAULT true |
| is_public | boolean | DEFAULT true |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| deleted_at | timestamp | NULLABLE |

---

## 🎯 TABLE : `promotions`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| name | varchar(150) | NOT NULL |
| slug | varchar(180) | UNIQUE |
| description | text | NULLABLE |
| type | enum(discount,bogo,bundle,flash_sale,clearance) | NOT NULL |
| discount_value | decimal(12,2) | NOT NULL |
| discount_type | enum(percentage,fixed) | DEFAULT 'percentage' |
| start_date | date | NOT NULL |
| end_date | date | NOT NULL |
| banner | varchar(255) | NULLABLE |
| banner_mobile | varchar(255) | NULLABLE |
| minimum_order | decimal(12,2) | DEFAULT 0 |
| usage_limit | int | NULLABLE |
| usage_count | int | DEFAULT 0 |
| is_active | boolean | DEFAULT true |
| is_featured | boolean | DEFAULT false |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| deleted_at | timestamp | NULLABLE |

---

## 🎯 TABLE : `flash_sales`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| name | varchar(150) | NOT NULL |
| description | text | NULLABLE |
| discount_percentage | decimal(5,2) | NOT NULL |
| maximum_discount | decimal(12,2) | NULLABLE |
| start_time | timestamp | NULLABLE |
| end_time | timestamp | NULLABLE |
| max_items_per_user | int | DEFAULT 1 |
| total_stock_limit | int | NULLABLE |
| sold_count | int | DEFAULT 0 |
| banner | varchar(255) | NULLABLE |
| is_active | boolean | DEFAULT true |
| is_featured | boolean | DEFAULT false |
| show_countdown | boolean | DEFAULT true |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| deleted_at | timestamp | NULLABLE |

---

## 🎯 TABLE : `banners`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| title | varchar(150) | NOT NULL |
| subtitle | varchar(255) | NULLABLE |
| description | text | NULLABLE |
| image | varchar(255) | NOT NULL |
| image_mobile | varchar(255) | NULLABLE |
| thumbnail | varchar(255) | NULLABLE |
| target_type | enum(product,category,promotion,external_url,none) | DEFAULT 'none' |
| target_id | varchar(100) | NULLABLE |
| external_url | varchar(255) | NULLABLE |
| position | enum(home_top,home_middle,home_bottom,category_page,product_page,sidebar) | DEFAULT 'home_top' |
| sort_order | int | DEFAULT 0 |
| start_date | date | NULLABLE |
| end_date | date | NULLABLE |
| is_active | boolean | DEFAULT true |
| is_animated | boolean | DEFAULT false |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| deleted_at | timestamp | NULLABLE |

---

## 🎯 TABLE : `newsletter_subscribers`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| email | varchar(150) | UNIQUE |
| name | varchar(150) | NULLABLE |
| user_id | bigint | FOREIGN KEY → users, SET NULL |
| status | enum(subscribed,unsubscribed,bounced,spam) | DEFAULT 'subscribed' |
| unsubscribe_token | varchar(100) | UNIQUE |
| subscribed_at | timestamp | DEFAULT CURRENT_TIMESTAMP |
| unsubscribed_at | timestamp | NULLABLE |
| last_email_sent_at | timestamp | NULLABLE |
| emails_sent_count | int | DEFAULT 0 |
| emails_opened_count | int | DEFAULT 0 |
| emails_clicked_count | int | DEFAULT 0 |
| preferences | varchar(255) | NULLABLE (JSON) |
| source | varchar(100) | NULLABLE |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |

---

## 📞 TABLE : `notifications`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| user_id | bigint | FOREIGN KEY → users, CASCADE |
| title | varchar(255) | NOT NULL |
| body | text | NOT NULL |
| image | varchar(255) | NULLABLE |
| icon | varchar(100) | NULLABLE |
| type | enum(order,promotion,price_alert,stock_alert,system,chat,review) | NOT NULL |
| target_type | varchar(100) | NULLABLE |
| target_id | varchar(100) | NULLABLE |
| is_read | boolean | DEFAULT false |
| read_at | timestamp | NULLABLE |
| sent_push | boolean | DEFAULT false |
| sent_email | boolean | DEFAULT false |
| sent_sms | boolean | DEFAULT false |
| sent_at | timestamp | NULLABLE |
| received_at | timestamp | NULLABLE |
| is_active | boolean | DEFAULT true |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| deleted_at | timestamp | NULLABLE |

---

## 📞 TABLE : `support_tickets`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| ticket_number | varchar(50) | UNIQUE |
| user_id | bigint | FOREIGN KEY → users, CASCADE |
| order_id | bigint | FOREIGN KEY → orders, SET NULL |
| subject | varchar(255) | NOT NULL |
| description | text | NOT NULL |
| category | enum(order,payment,delivery,product,account,technical,other) | DEFAULT 'other' |
| priority | enum(low,medium,high,urgent) | DEFAULT 'medium' |
| status | enum(open,pending,resolved,closed,reopened) | DEFAULT 'open' |
| assigned_to | bigint | FOREIGN KEY → users, SET NULL |
| resolved_at | timestamp | NULLABLE |
| closed_at | timestamp | NULLABLE |
| last_reply_at | timestamp | NULLABLE |
| satisfaction_rating | int | NULLABLE (1-5) |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| deleted_at | timestamp | NULLABLE |

---

## 📞 TABLE : `ticket_messages`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| ticket_id | bigint | FOREIGN KEY → support_tickets, CASCADE |
| user_id | bigint | FOREIGN KEY → users, CASCADE |
| message | text | NOT NULL |
| attachment | varchar(255) | NULLABLE |
| attachment_name | varchar(255) | NULLABLE |
| attachment_size | int | NULLABLE |
| sender_type | enum(user,admin,system) | DEFAULT 'user' |
| is_read | boolean | DEFAULT false |
| read_at | timestamp | NULLABLE |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |

---

## 📞 TABLE : `faqs`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| question | varchar(255) | NOT NULL |
| answer | text | NOT NULL |
| category | enum(general,orders,shipping,payments,returns,account,products) | DEFAULT 'general' |
| views_count | int | DEFAULT 0 |
| helpful_count | int | DEFAULT 0 |
| position | int | DEFAULT 0 |
| is_active | boolean | DEFAULT true |
| is_featured | boolean | DEFAULT false |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| deleted_at | timestamp | NULLABLE |

---

## 👑 TABLE : `admin_logs`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| user_id | bigint | FOREIGN KEY → users, CASCADE |
| action | varchar(150) | NOT NULL |
| module | varchar(100) | NULLABLE |
| description | text | NULLABLE |
| target_type | varchar(100) | NULLABLE |
| target_id | varchar(100) | NULLABLE |
| old_data | text | NULLABLE (JSON) |
| new_data | text | NULLABLE (JSON) |
| ip_address | varchar(45) | NULLABLE |
| user_agent | text | NULLABLE |
| url | varchar(255) | NULLABLE |
| method | varchar(10) | NULLABLE |
| is_successful | boolean | DEFAULT true |
| error_message | text | NULLABLE |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |

---

## 👑 TABLE : `settings`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| key | varchar(100) | UNIQUE |
| value | text | NULLABLE |
| group | varchar(100) | NULLABLE |
| type | enum(string,integer,float,boolean,json,array,file) | DEFAULT 'string' |
| description | text | NULLABLE |
| is_public | boolean | DEFAULT false |
| is_active | boolean | DEFAULT true |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |

---

## 👑 TABLE : `currencies`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| name | varchar(100) | NOT NULL |
| code | varchar(10) | UNIQUE |
| symbol | varchar(10) | NOT NULL |
| exchange_rate | decimal(15,6) | DEFAULT 1 |
| decimal_separator | varchar(5) | DEFAULT '.' |
| thousand_separator | varchar(5) | DEFAULT ',' |
| decimal_places | int | DEFAULT 2 |
| symbol_position | enum(before,after) | DEFAULT 'after' |
| is_default | boolean | DEFAULT false |
| is_active | boolean | DEFAULT true |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |

---

## ⚙️ TABLE : `languages`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| name | varchar(100) | NOT NULL |
| code | varchar(10) | UNIQUE |
| native_name | varchar(100) | NOT NULL |
| flag | varchar(255) | NULLABLE |
| flag_emoji | varchar(10) | NULLABLE |
| direction | enum(ltr,rtl) | DEFAULT 'ltr' |
| is_default | boolean | DEFAULT false |
| is_active | boolean | DEFAULT true |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |

---

## ⚙️ TABLE : `countries`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| name | varchar(150) | NOT NULL |
| code | varchar(10) | UNIQUE |
| iso3 | varchar(10) | NULLABLE |
| phone_code | varchar(10) | NULLABLE |
| flag | varchar(255) | NULLABLE |
| flag_emoji | varchar(10) | NULLABLE |
| currency_code | varchar(10) | NULLABLE |
| currency_name | varchar(100) | NULLABLE |
| currency_symbol | varchar(10) | NULLABLE |
| language_code | varchar(10) | NULLABLE |
| is_delivery_available | boolean | DEFAULT true |
| delivery_fee | decimal(12,2) | DEFAULT 0 |
| delivery_days | int | DEFAULT 7 |
| latitude | decimal(10,8) | NULLABLE |
| longitude | decimal(11,8) | NULLABLE |
| is_active | boolean | DEFAULT true |
| is_default | boolean | DEFAULT false |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| deleted_at | timestamp | NULLABLE |

---

## ⚙️ TABLE : `cities`

| Colonne | Type | Contraintes |
|---------|------|-------------|
| id | bigint | PRIMARY KEY |
| country_id | bigint | FOREIGN KEY → countries, CASCADE |
| name | varchar(150) | NOT NULL |
| code | varchar(50) | NULLABLE |
| postal_code | varchar(20) | NULLABLE |
| latitude | decimal(10,8) | NULLABLE |
| longitude | decimal(11,8) | NULLABLE |
| is_delivery_available | boolean | DEFAULT true |
| delivery_fee | decimal(12,2) | DEFAULT 0 |
| delivery_days | int | NULLABLE |
| minimum_order | decimal(12,2) | DEFAULT 0 |
| population | int | NULLABLE |
| is_active | boolean | DEFAULT true |
| is_popular | boolean | DEFAULT false |
| created_at | timestamp | NULLABLE |
| updated_at | timestamp | NULLABLE |
| deleted_at | timestamp | NULLABLE |
| | | UNIQUE(country_id, name) |
```

---

📋 RÉSUMÉ

Ce fichier documente toutes les colonnes des 50 tables avec :

· ✅ Types de données
· ✅ Contraintes (NOT NULL, UNIQUE, DEFAULT)
· ✅ Clés étrangères
· ✅ SoftDeletes
· ✅ Tables pivot avec UNIQUE

---

Maintenant le nouveau chat aura TOUTES les informations sur la base de données ! 🚀