<?php

namespace Tests\Feature;

use App\Models\AppReview;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a default user to own stores
        $this->user = User::create([
            'username' => 'store_owner',
            'email' => 'owner@example.com',
            'password' => bcrypt('password123'),
        ]);
    }

    /**
     * Test list of stores.
     */
    public function test_get_stores_list_paginated_and_sorted_newest_first()
    {
        // Create 2 stores with different timestamps
        $store1 = Store::create([
            'user_id' => $this->user->id,
            'name' => 'Store One',
            'description' => 'First store description',
            'image' => 'images/store1.jpg',
        ]);
        $store1->created_at = now()->subDay();
        $store1->save();

        $store2 = Store::create([
            'user_id' => $this->user->id,
            'name' => 'Store Two',
            'description' => 'Second store description',
            'image' => 'images/store2.jpg',
        ]);

        $response = $this->getJson('/api/stores');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'current_page',
                'data' => [
                    '*' => ['id', 'name', 'description', 'image']
                ],
                'total'
            ]
        ]);

        $data = $response->json('data.data');
        $this->assertCount(2, $data);
        
        // Assert sorting (newest first: Store Two should be first)
        $this->assertEquals($store2->id, $data[0]['id']);
        $this->assertEquals($store1->id, $data[1]['id']);
        
        // Assert no extra columns (like user_id, created_at, updated_at) are present in the list items
        $this->assertArrayNotHasKey('user_id', $data[0]);
        $this->assertArrayNotHasKey('created_at', $data[0]);
    }

    /**
     * Test store details with products.
     */
    public function test_get_store_details_with_products()
    {
        $store = Store::create([
            'user_id' => $this->user->id,
            'name' => 'Fish Store',
            'description' => 'Selling fresh seafood',
        ]);

        $product1 = Product::create([
            'store_id' => $store->id,
            'name' => 'Tuna Fish',
            'description' => 'Fresh yellowfin tuna',
            'price' => 150000.00,
            'stock' => 10,
        ]);

        $product2 = Product::create([
            'store_id' => $store->id,
            'name' => 'Salmon',
            'description' => 'Atlantic salmon fillet',
            'price' => 250000.00,
            'stock' => 5,
        ]);

        $response = $this->getJson('/api/stores/' . $store->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'user_id',
                'name',
                'description',
                'image',
                'products' => [
                    '*' => ['id', 'store_id', 'name', 'description', 'price', 'stock', 'image']
                ]
            ]
        ]);

        $response->assertJsonPath('data.name', 'Fish Store');
        $this->assertCount(2, $response->json('data.products'));
    }

    /**
     * Test store details 404.
     */
    public function test_get_store_details_not_found()
    {
        $response = $this->getJson('/api/stores/999');

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Toko tidak ditemukan'
        ]);
    }

    /**
     * Test list of products with pagination and sorting.
     */
    public function test_get_products_list_paginated_and_sorted_newest_first()
    {
        $store = Store::create([
            'user_id' => $this->user->id,
            'name' => 'Seafood Market',
        ]);

        $product1 = Product::create([
            'store_id' => $store->id,
            'name' => 'Product One',
            'price' => 1000.00,
            'stock' => 5,
        ]);
        $product1->created_at = now()->subDay();
        $product1->save();

        $product2 = Product::create([
            'store_id' => $store->id,
            'name' => 'Product Two',
            'price' => 2000.00,
            'stock' => 10,
        ]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'current_page',
                'data' => [
                    '*' => ['id', 'store_id', 'name', 'description', 'price', 'stock', 'image']
                ]
            ]
        ]);

        $data = $response->json('data.data');
        $this->assertCount(2, $data);
        // Assert newest first sorting
        $this->assertEquals($product2->id, $data[0]['id']);
        $this->assertEquals($product1->id, $data[1]['id']);
        
        // Assert no extra fields leaked
        $this->assertArrayNotHasKey('created_at', $data[0]);
    }

    /**
     * Test product list filtered by store_id.
     */
    public function test_get_products_filtered_by_store_id()
    {
        $store1 = Store::create(['user_id' => $this->user->id, 'name' => 'Store One']);
        $store2 = Store::create(['user_id' => $this->user->id, 'name' => 'Store Two']);

        Product::create(['store_id' => $store1->id, 'name' => 'S1 Product', 'price' => 100, 'stock' => 1]);
        Product::create(['store_id' => $store2->id, 'name' => 'S2 Product', 'price' => 200, 'stock' => 1]);

        $response = $this->getJson('/api/products?store_id=' . $store1->id);

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals('S1 Product', $data[0]['name']);
    }

    /**
     * Test product search.
     */
    public function test_get_products_searched_by_name()
    {
        $store = Store::create(['user_id' => $this->user->id, 'name' => 'Store']);

        Product::create(['store_id' => $store->id, 'name' => 'Fresh Tuna Fish', 'price' => 100, 'stock' => 1]);
        Product::create(['store_id' => $store->id, 'name' => 'Canned Salmon Fillet', 'price' => 200, 'stock' => 1]);

        // Search 'tuna'
        $response = $this->getJson('/api/products?search=Tuna');
        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals('Fresh Tuna Fish', $data[0]['name']);

        // Search 'fillet'
        $response2 = $this->getJson('/api/products?search=fillet');
        $data2 = $response2->json('data.data');
        $this->assertCount(1, $data2);
        $this->assertEquals('Canned Salmon Fillet', $data2[0]['name']);
    }

    /**
     * Test product details with store info (only id and name).
     */
    public function test_get_product_details_with_store_info()
    {
        $store = Store::create([
            'user_id' => $this->user->id,
            'name' => 'Fish Market',
            'description' => 'A great market',
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Golden Crab',
            'description' => 'Tasty gold crab',
            'price' => 500000.00,
            'stock' => 3,
        ]);

        $response = $this->getJson('/api/products/' . $product->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'store_id',
                'name',
                'description',
                'price',
                'stock',
                'image',
                'store' => ['id', 'name']
            ]
        ]);

        // Verify store info structure (description should NOT be inside store object in product details response)
        $this->assertArrayNotHasKey('description', $response->json('data.store'));
        $this->assertEquals('Fish Market', $response->json('data.store.name'));
    }

    /**
     * Test product details 404.
     */
    public function test_get_product_details_not_found()
    {
        $response = $this->getJson('/api/products/999');

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Produk tidak ditemukan'
        ]);
    }

    /**
     * Test app reviews list.
     */
    public function test_get_reviews_list_paginated_and_sorted_newest_first()
    {
        $review1 = AppReview::create([
            'reviewer_name' => 'Andy',
            'rating' => 4,
            'comment' => 'Pretty good app',
            'created_at' => now()->subHour(),
        ]);

        $review2 = AppReview::create([
            'reviewer_name' => 'Bella',
            'rating' => 5,
            'comment' => 'Loved it!',
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/reviews');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'current_page',
                'data' => [
                    '*' => ['reviewer_name', 'rating', 'comment', 'created_at']
                ]
            ]
        ]);

        $data = $response->json('data.data');
        $this->assertCount(2, $data);
        // Bella (newer) should be first
        $this->assertEquals('Bella', $data[0]['reviewer_name']);
        $this->assertEquals('Andy', $data[1]['reviewer_name']);
    }
}
