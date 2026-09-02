📦 FICHIER : docs/08-prochaines-etapes.md (Laravel API)

---

```markdown
# 🚀 PROCHAINES ÉTAPES - SHOPPRO API

---

## 📋 ORDRE DE PRIORITÉ

### 🔴 PRIORITÉ HAUTE (Corrections immédiates)

1. **Corriger ProductController::show()**
   - Fichier : `app/Http/Controllers/Api/Product/ProductController.php`
   - Supprimer `take(10)` dans la relation `reviews`
   - Cause : Erreur SQL avec MySQL

2. **Vérifier les seeders anti-doublons**
   - Ajouter `truncate()` ou `updateOrCreate()` dans les seeders
   - Éviter les doublons si exécutés deux fois

3. **Ajouter des images variées**
   - Remplacer `test.jpg` par des images réelles
   - Mettre à jour la BD avec des chemins différents

---

### 🟡 PRIORITÉ MOYENNE (Phase : Favoris)

1. **Créer FavoriteController**
   - Fichier : `app/Http/Controllers/Api/Favorite/FavoriteController.php`
   - Méthodes : index, toggle, destroy

2. **Ajouter les routes**
   ```php
   Route::get('/favorites', [FavoriteController::class, 'index']);
   Route::post('/favorites/toggle/{productId}', [FavoriteController::class, 'toggle']);
   Route::delete('/favorites/{id}', [FavoriteController::class, 'destroy']);
```

---

🟡 PRIORITÉ MOYENNE (Phase : Avis)

1. Créer ReviewController
   · Méthodes : store, update, destroy
   · Validation des avis
2. Ajouter les routes
   ```php
   Route::post('/reviews', [ReviewController::class, 'store']);
   Route::put('/reviews/{id}', [ReviewController::class, 'update']);
   Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);
   ```

---

🟡 PRIORITÉ MOYENNE (Phase : Panier)

1. Créer CartController
   · Méthodes : index, add, update, remove, clear
2. Ajouter les routes
   ```php
   Route::get('/cart', [CartController::class, 'index']);
   Route::post('/cart/add', [CartController::class, 'add']);
   Route::put('/cart/update/{id}', [CartController::class, 'update']);
   Route::delete('/cart/remove/{id}', [CartController::class, 'remove']);
   Route::delete('/cart/clear', [CartController::class, 'clear']);
   ```

---

🟠 PRIORITÉ IMPORTANTE (Phase : Commandes)

1. Créer OrderController
   · Méthodes : index, store, show, cancel, track
2. Ajouter les routes
   ```php
   Route::get('/orders', [OrderController::class, 'index']);
   Route::post('/orders', [OrderController::class, 'store']);
   Route::get('/orders/{id}', [OrderController::class, 'show']);
   Route::put('/orders/{id}/cancel', [OrderController::class, 'cancel']);
   Route::get('/orders/{id}/track', [OrderController::class, 'track']);
   ```

---

🟠 PRIORITÉ IMPORTANTE (Phase : Paiement)

1. Créer PaymentController
   · Méthodes : initiate, verify, callback
2. Ajouter les routes
   ```php
   Route::post('/payments/initiate', [PaymentController::class, 'initiate']);
   Route::post('/payments/verify', [PaymentController::class, 'verify']);
   Route::post('/payments/callback', [PaymentController::class, 'callback']);
   ```

---

🟢 PRIORITÉ BASSE (Phase : Admin Dashboard)

1. Créer DashboardController
   · Méthodes : stats, sales, users
2. Ajouter les routes
   ```php
   Route::get('/admin/stats', [DashboardController::class, 'stats']);
   Route::get('/admin/stats/sales', [DashboardController::class, 'sales']);
   Route::get('/admin/stats/users', [DashboardController::class, 'users']);
   ```

---

📝 FICHIERS À CRÉER

```
app/Http/Controllers/Api/
├── Favorite/
│   └── FavoriteController.php
├── Review/
│   └── ReviewController.php
├── Cart/
│   └── CartController.php
├── Order/
│   └── OrderController.php
└── Payment/
    └── PaymentController.php
```

---

📝 NOTE POUR LE NOUVEAU CHAT

Quand tu reprends dans un nouveau chat :

1. Lis tous les fichiers dans docs/
2. Commence par les corrections HAUTE priorité
3. Puis crée les contrôleurs manquants
4. Ajoute les routes correspondantes
5. Teste avec curl après chaque contrôleur
6. Garde le même pattern de réponse JSON
7. Utilise les mêmes validations et messages en français

```

---

## 📋 RÉSUMÉ

Ce fichier documente :
- ✅ Les corrections prioritaires
- ✅ Les contrôleurs à créer
- ✅ Les routes à ajouter
- ✅ L'ordre de priorité

---

## 🎉 BASE DE CONNAISSANCES API TERMINÉE !

### Les 9 fichiers créés :

| Fichier | Contenu |
|---------|---------|
| `00-contexte.md` | Vue d'ensemble |
| `01-architecture.md` | Architecture |
| `02-database.md` | Base de données |
| `03-routes-api.md` | Routes |
| `04-controllers.md` | Contrôleurs |
| `05-models.md` | Modèles |
| `06-resources.md` | Resources |
| `07-progress.md` | Avancement |
| `08-prochaines-etapes.md` | À faire |

---

**Tu peux maintenant envoyer ces fichiers à un nouveau chat pour reprendre le projet API !** 🚀