<?php
// tests/Feature/Api/SearchTest.php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Image;
use App\Services\ElasticSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Mockery;
use Mockery\MockInterface;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $activeUser;
    protected User $inactiveUser;
    protected Image $testImage;

    protected function setUp(): void
    {
        parent::setUp();

        // Создаем тестовых пользователей
        $this->activeUser = User::factory()->create([
            'role' => 'buyer',
            'api_key' => 'test_active_key',
            'request_limit' => 1000,
            'requests_used' => 0,
            'subscription_valid_until' => now()->addYear(),
            'is_active' => true,
        ]);

        $this->inactiveUser = User::factory()->create([
            'role' => 'buyer', 
            'api_key' => 'test_inactive_key',
            'request_limit' => 1000,
            'requests_used' => 1000, // лимит исчерпан
            'subscription_valid_until' => now()->subDay(), // просрочена
            'is_active' => false,
        ]);

        // Создаем тестовое изображение
        $this->testImage = Image::factory()->create([
            'title' => 'Test Red Sneakers',
            'category' => 'footwear',
            'brand' => 'Nike',
            'is_active' => true,
        ]);

    }
      

    #[Test]
    public function it_returns_401_without_api_key()
    {
        $response = $this->getJson('/api/v1/search?q=sneakers');

        $response->assertStatus(401)
                ->assertJson(['error' => 'API key required']);
    }

    #[Test]
    public function it_returns_403_with_invalid_api_key()
    {
        $response = $this->withHeaders(['X-API-Key' => 'invalid_key'])
                        ->getJson('/api/v1/search?q=sneakers');

        $response->assertStatus(403)
                ->assertJson(['error' => 'Invalid or expired API key']);
    }

    #[Test]
    public function it_returns_403_when_subscription_inactive()
    {
        $response = $this->withHeaders(['X-API-Key' => 'test_inactive_key'])
                        ->getJson('/api/v1/search?q=sneakers');

        $response->assertStatus(403);
    }

    #[Test]
    public function it_returns_search_results_with_valid_api_key()
    {
        $response = $this->withHeaders(['X-API-Key' => 'test_active_key'])
                        ->getJson('/api/v1/search?q=sneakers');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'results',
                    'total',
                    'page',
                    'limit'
                ]);
    }

    #[Test]
    public function it_increments_request_count_on_successful_search()
    {
        $initialCount = $this->activeUser->requests_used;

        $this->withHeaders(['X-API-Key' => 'test_active_key'])
            ->getJson('/api/v1/search?q=sneakers');

        $this->activeUser->refresh();
        $this->assertEquals($initialCount + 1, $this->activeUser->requests_used);
    }

    #[Test]
    public function it_filters_by_category()
    {
        // 1. Проверяем ЧТО передается в сервис
        $mock = $this->createMock(ElasticSearchService::class);
        $mock->method('search')
             ->with($this->callback(function ($params) {
                 return $params['category'] === 'footwear'; // ← проверка входных данных
             }))
             ->willReturn([
                 'hits' => [
                     'hits' => [[
                         '_source' => [
                             'id' => 1,
                             'title' => 'Test Red Sneakers',
                             'brand' => 'Nike', 
                             'original_filename' => '123',
                             'category' => 'footwear' // ← проверка выходных данных
                         ]
                     ]],
                     'total' => ['value' => 1]
                 ]
             ]);

        // 2. Внедряем мок в контейнер
        $this->app->instance(ElasticSearchService::class, $mock);
        $response = $this->withHeaders(['X-API-Key' => 'test_active_key'])
                        ->getJson('/api/v1/search?category=footwear');
                        
        $response->assertStatus(200)
                ->assertJsonFragment(['category' => 'footwear']);
    }

    #[Test]
    public function it_filters_by_brand()
    {
        // 1. Проверяем ЧТО передается в сервис
        $mock = $this->createMock(ElasticSearchService::class);
        $mock->method('search')
             ->with($this->callback(function ($params) {
                 return $params['brand'] === 'Nike'; 
             }))
             ->willReturn([
                 'hits' => [
                     'hits' => [[
                         '_source' => [
                             'id' => 1,
                             'title' => 'Test Red Sneakers',
                             'brand' => 'Nike', 
                             'original_filename' => '123',
                             'category' => 'footwear' 
                         ]
                     ]],
                     'total' => ['value' => 1]
                 ]
             ]);

        // 2. Внедряем мок в контейнер
        $this->app->instance(ElasticSearchService::class, $mock);
        $response = $this->withHeaders(['X-API-Key' => 'test_active_key'])
                        ->getJson('/api/v1/search?brand=Nike');

        $response->assertStatus(200)
                ->assertJsonFragment(['brand' => 'Nike']);
    }

    #[Test]
    public function it_returns_empty_results_for_no_matches()
    {
        $response = $this->withHeaders(['X-API-Key' => 'test_active_key'])
                        ->getJson('/api/v1/search?q=nonexistent');

        $response->assertStatus(200)
                ->assertJson(['results' => [], 'total' => 0]);
    }
}
