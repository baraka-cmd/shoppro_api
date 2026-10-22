<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

use App\Models\User;
use App\Models\User\Role;
use App\Models\Seller\SellerProfile;
use App\Models\Catalog\Product;
use App\Models\Catalog\Category;
use App\Models\Catalog\Brand;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Review\Review;

/**
 * Tests de sécurité du module vendeur.
 *
 * Vérifie l'isolation entre vendeurs, la protection des rôles
 * et l'application correcte des Policies + Middlewares.
 *
 * Lancer :
 *   php artisan test --filter=SellerSecurityTest
 *
 * Ou un test précis :
 *   php artisan test --filter=test_vendor_cannot_view_other_vendor_product
 *
 * @package Tests\Feature
 */
class SellerSecurityTest extends TestCase
{
    use RefreshDatabase;

    // ============================================
    // UTILISATEURS DE TEST
    // ============================================

    private User $admin;
    private User $sellerA;
    private User $sellerB;
    private User $client;
    private User $pendingSeller;

    // ============================================
    // RESSOURCES DE TEST
    // ============================================

    private Category $category;
    private Brand $brand;
    private Product $productA;
    private Product $productB;
    private Order $orderOfA;
    private Review $reviewOfA;

    // ============================================
    // SETUP
    // ============================================

    protected function setUp(): void
    {
        parent::setUp();

        // Rôles de base
        $adminRole    = Role::firstOrCreate(['name' => 'admin'],   ['display_name' => 'Admin']);
        $sellerRole   = Role::firstOrCreate(['name' => 'seller'],  ['display_name' => 'Vendeur']);
        $clientRole   = Role::firstOrCreate(['name' => 'client'],  ['display_name' => 'Client']);

        // Admin
        $this->admin = User::create([
            'name'      => 'Admin Test',
            'email'     => 'admin.test@shoppro.com',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $this->admin->roles()->attach($adminRole->id);

        // Vendeur A (KYC verified, actif)
        $this->sellerA = User::create([
            'name'      => 'Vendeur A',
            'email'     => 'seller.a@shoppro.com',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $this->sellerA->roles()->attach($sellerRole->id);
        SellerProfile::create([
            'user_id'      => $this->sellerA->id,
            'shop_name'    => 'Boutique A',
            'shop_slug'    => 'boutique-a',
            'kyc_status'   => 'verified',
            'is_active'    => true,
            'kyc_verified_at' => now(),
        ]);

        // Vendeur B (KYC verified, actif)
        $this->sellerB = User::create([
            'name'      => 'Vendeur B',
            'email'     => 'seller.b@shoppro.com',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $this->sellerB->roles()->attach($sellerRole->id);
        SellerProfile::create([
            'user_id'      => $this->sellerB->id,
            'shop_name'    => 'Boutique B',
            'shop_slug'    => 'boutique-b',
            'kyc_status'   => 'verified',
            'is_active'    => true,
            'kyc_verified_at' => now(),
        ]);

        // Vendeur pending (KYC non validé)
        $this->pendingSeller = User::create([
            'name'      => 'Vendeur Pending',
            'email'     => 'seller.pending@shoppro.com',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $this->pendingSeller->roles()->attach($sellerRole->id);
        SellerProfile::create([
            'user_id'    => $this->pendingSeller->id,
            'shop_name'  => 'Boutique Pending',
            'shop_slug'  => 'boutique-pending',
            'kyc_status' => 'pending',
            'is_active'  => true,
        ]);

        // Client
        $this->client = User::create([
            'name'      => 'Client Test',
            'email'     => 'client.test@shoppro.com',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $this->client->roles()->attach($clientRole->id);

        // Catégorie + marque
        $this->category = Category::create([
            'name' => 'Test Cat',
            'slug' => 'test-cat',
        ]);

        $this->brand = Brand::create([
            'name' => 'Test Brand',
            'slug' => 'test-brand',
        ]);

        // Produit de A
        $this->productA = Product::create([
            'name'        => 'Produit A',
            'slug'        => 'produit-a',
            'price'       => 10000,
            'stock'       => 10,
            'sku'         => 'SKU-A-001',
            'category_id' => $this->category->id,
            'brand_id'    => $this->brand->id,
            'seller_id'   => $this->sellerA->id,
            'is_active'   => true,
        ]);

        // Produit de B
        $this->productB = Product::create([
            'name'        => 'Produit B',
            'slug'        => 'produit-b',
            'price'       => 20000,
            'stock'       => 5,
            'sku'         => 'SKU-B-001',
            'category_id' => $this->category->id,
            'brand_id'    => $this->brand->id,
            'seller_id'   => $this->sellerB->id,
            'is_active'   => true,
        ]);

        // Commande passée par un client, contenant les produits de A
        $this->orderOfA = Order::create([
            'order_number' => 'CMD-TEST-001',
            'user_id'      => $this->client->id,
            'subtotal'     => 10000,
            'total'        => 10000,
            'status'       => 'pending',
            'payment_status' => 'pending',
            'currency'     => 'FCFA',
        ]);

        OrderItem::create([
            'order_id'     => $this->orderOfA->id,
            'product_id'   => $this->productA->id,
            'seller_id'    => $this->sellerA->id,
            'quantity'     => 1,
            'unit_price'   => 10000,
            'total_price'  => 10000,
            'product_name' => 'Produit A',
            'product_sku'  => 'SKU-A-001',
            'item_status'  => 'pending',
            'seller_status'=> 'to_accept',
        ]);

        // Avis sur produit A
        $this->reviewOfA = Review::create([
            'user_id'    => $this->client->id,
            'product_id' => $this->productA->id,
            'seller_id'  => $this->sellerA->id,
            'rating'     => 5,
            'content'    => 'Excellent produit',
            'status'     => 'approved',
        ]);
    }

    // ============================================
    // TESTS : ISOLATION VENDEUR ↔ VENDEUR
    // ============================================

    /**
     * Test 1 : Vendeur A ne peut PAS voir le produit de B.
     */
    public function test_vendor_cannot_view_other_vendor_product(): void
    {
        Sanctum::actingAs($this->sellerA);

        $response = $this->getJson("/api/v1/seller/products/{$this->productB->id}");

        // 404 attendu (ne pas révéler l'existence du produit)
        $response->assertStatus(404);
    }

    /**
     * Test 2 : Vendeur A ne peut PAS modifier le produit de B.
     */
    public function test_vendor_cannot_update_other_vendor_product(): void
    {
        Sanctum::actingAs($this->sellerA);

        $response = $this->putJson("/api/v1/seller/products/{$this->productB->id}", [
            'name'  => 'Hacked',
            'price' => 1,
        ]);

        // 403 attendu (produit existe mais pas à lui)
        $response->assertStatus(403);
    }

    /**
     * Test 3 : Vendeur A ne peut PAS supprimer le produit de B.
     */
    public function test_vendor_cannot_delete_other_vendor_product(): void
    {
        Sanctum::actingAs($this->sellerA);

        $response = $this->deleteJson("/api/v1/seller/products/{$this->productB->id}");

        $response->assertStatus(403);
    }

    /**
     * Test 4 : Vendeur B ne peut PAS voir la commande d'A
     * (commande ne contient aucun item de B).
     */
    public function test_vendor_cannot_view_other_vendor_order(): void
    {
        Sanctum::actingAs($this->sellerB);

        $response = $this->getJson("/api/v1/seller/orders/{$this->orderOfA->id}");

        $response->assertStatus(404);
    }

    // ============================================
    // TESTS : ISOLATION RÔLES
    // ============================================

    /**
     * Test 5 : Client ne peut PAS accéder à /seller/*.
     */
    public function test_client_cannot_access_seller_routes(): void
    {
        Sanctum::actingAs($this->client);

        $response = $this->getJson('/api/v1/seller/products');

        $response->assertStatus(403);
    }

    /**
     * Test 6 : Vendeur ne peut PAS accéder à /admin/*.
     */
    public function test_vendor_cannot_access_admin_routes(): void
    {
        Sanctum::actingAs($this->sellerA);

        $response = $this->getJson('/api/v1/admin/users');

        $response->assertStatus(403);
    }

    /**
     * Test 7 : Admin peut tout voir sur /admin/*.
     */
    public function test_admin_can_access_admin_routes(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/admin/users');

        $response->assertStatus(200);
    }

    // ============================================
    // TESTS : KYC & STATUT VENDEUR
    // ============================================

    /**
     * Test 8 : Vendeur KYC pending est bloqué sur /seller/*.
     */
    public function test_pending_kyc_vendor_is_blocked(): void
    {
        Sanctum::actingAs($this->pendingSeller);

        $response = $this->getJson('/api/v1/seller/products');

        $response->assertStatus(403);
        $response->assertJsonFragment(['code' => 'SELLER_NOT_ACTIVE']);
    }

    /**
     * Test 9 : Vendeur inactif (is_active = false) est bloqué.
     */
    public function test_inactive_vendor_is_blocked(): void
    {
        $this->sellerA->sellerProfile->update(['is_active' => false]);

        Sanctum::actingAs($this->sellerA);

        $response = $this->getJson('/api/v1/seller/products');

        $response->assertStatus(403);
    }

    /**
     * Test 10 : Admin peut accéder à /seller/* (super-pouvoir).
     */
    public function test_admin_can_access_seller_routes(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/seller/products');

        // 200 attendu (l'admin passe le middleware seller.active)
        $response->assertStatus(200);
    }

    // ============================================
    // TESTS : AUTHENTIFICATION
    // ============================================

    /**
     * Test 11 : Non authentifié → 401 sur /seller/*.
     */
    public function test_unauthenticated_cannot_access_seller_routes(): void
    {
        $response = $this->getJson('/api/v1/seller/products');

        $response->assertStatus(401);
    }

    // ============================================
    // TESTS : POLICIES
    // ============================================

    /**
     * Test 12 : Vendeur KYC pending ne peut PAS publier un produit.
     */
    public function test_pending_kyc_vendor_cannot_publish_product(): void
    {
        // Créer un produit pour le pendingSeller
        $product = Product::create([
            'name'        => 'Produit Pending',
            'slug'        => 'produit-pending',
            'price'       => 5000,
            'stock'       => 1,
            'sku'         => 'SKU-P-001',
            'category_id' => $this->category->id,
            'seller_id'   => $this->pendingSeller->id,
            'is_active'   => false,
        ]);

        $response = $this->actingAs($this->pendingSeller)
            ->postJson("/api/v1/seller/products/{$product->id}/publish");

        $response->assertStatus(403);
    }

    /**
     * Test 13 : Vendeur B ne peut PAS répondre à un avis sur un produit d'A.
     */
    public function test_vendor_cannot_respond_to_other_vendor_review(): void
    {
        Sanctum::actingAs($this->sellerB);

        $response = $this->postJson(
            "/api/v1/seller/reviews/{$this->reviewOfA->id}/respond",
            ['response' => 'Merci !']
        );

        // 404 (avis existe mais pas sur ses produits)
        $response->assertStatus(404);
    }

    // ============================================
    // TESTS : TRAIT SCOPESBYSELLER
    // ============================================

    /**
     * Test 14 : Le scope `forSeller` isole correctement les données.
     */
    public function test_for_seller_scope_isolates_data(): void
    {
        // Vendeur A ne voit que ses produits
        $productsOfA = Product::forSeller($this->sellerA)->get();
        $this->assertCount(1, $productsOfA);
        $this->assertEquals($this->productA->id, $productsOfA->first()->id);

        // Vendeur B ne voit que ses produits
        $productsOfB = Product::forSeller($this->sellerB)->get();
        $this->assertCount(1, $productsOfB);
        $this->assertEquals($this->productB->id, $productsOfB->first()->id);

        // Client → 0 résultats (pas de fuite)
        $productsOfClient = Product::forSeller($this->client)->get();
        $this->assertCount(0, $productsOfClient);

        // Utilisateur null → 0 résultats
        $productsOfNull = Product::forSeller(null)->get();
        $this->assertCount(0, $productsOfNull);
    }

    /**
     * Test 15 : La méthode `belongsToSeller` fonctionne.
     */
    public function test_belongs_to_seller_method(): void
    {
        // A possède son propre produit
        $this->assertTrue($this->productA->belongsToSeller($this->sellerA));

        // A ne possède PAS le produit de B
        $this->assertFalse($this->productB->belongsToSeller($this->sellerA));

        // B possède son propre produit
        $this->assertTrue($this->productB->belongsToSeller($this->sellerB));

        // Admin possède "tout" (super-pouvoir)
        $this->assertTrue($this->productA->belongsToSeller($this->admin));

        // Client ne possède rien
        $this->assertFalse($this->productA->belongsToSeller($this->client));

        // Null ne possède rien
        $this->assertFalse($this->productA->belongsToSeller(null));
    }
}