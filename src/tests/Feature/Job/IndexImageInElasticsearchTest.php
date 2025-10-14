<?php
// tests/Feature/Job/IndexImageInElasticsearchTest.php

namespace Tests\Feature\Job;

use App\Jobs\IndexImageInElasticsearch;
use App\Models\Image;
use App\Services\ElasticSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Mockery;

class IndexImageInElasticsearchTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_indexes_image_in_elasticsearch()
    {
        $image = Image::factory()->create([
            'title' => 'Test Image',
            'category' => 'electronics',
            'brand' => 'Sony',
            'is_active' => true,
        ]);

        // Мокаем ElasticSearchService
        $mock = Mockery::mock(ElasticSearchService::class);
        $mock->shouldReceive('indexImage')
            ->once()
            ->with($image->getSearchableData());

        $job = new IndexImageInElasticsearch($image);
        $job->handle($mock);

        // Явный assertion чтобы PHPUnit знал что тест выполнился
        $this->assertTrue(true);
        
        // Или проверяем что изображение существует в БД
        $this->assertDatabaseHas('images', [
            'id' => $image->id,
            'title' => 'Test Image'
        ]);
    }

    #[Test]
    public function it_does_not_index_inactive_images()
    {
        $inactiveImage = Image::factory()->create([
            'is_active' => false,
        ]);

        $mock = Mockery::mock(ElasticSearchService::class);
        $mock->shouldReceive('indexImage')->never();

        $this->app->instance(ElasticSearchService::class, $mock);

        $job = new IndexImageInElasticsearch($inactiveImage);
        $job->handle($mock);
        
        // Явный assertion чтобы PHPUnit знал что тест выполнился
        $this->assertTrue(true);
    }

    #[Test]
    public function it_handles_elasticsearch_errors_gracefully()
    {
        $image = Image::factory()->create();

        // Мокаем сервис чтобы он бросал исключение
        $mock = Mockery::mock(ElasticSearchService::class);
        $mock->shouldReceive('indexImage')
            ->andThrow(new \Exception('ElasticSearch unavailable'));

        $this->app->instance(ElasticSearchService::class, $mock);

        $job = new IndexImageInElasticsearch($image);
        
        // Job должен завершиться без фатальной ошибки
        $job->handle($mock);

        // Если мы здесь - значит исключение было обработано
        $this->assertTrue(true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
