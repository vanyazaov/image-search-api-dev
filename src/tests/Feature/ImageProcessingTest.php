<?php
// tests/Feature/ImageProcessingTest.php

namespace Tests\Feature;

use App\Jobs\ProcessImageUpload;
use App\Jobs\IndexImageInElasticsearch;
use App\Models\Image;
use App\Models\User;
use App\Services\ElasticSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Mockery;

class ImageProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['role' => 'admin']);
        Storage::fake('local');
        Queue::fake();
    }

    #[Test]
    public function admin_can_upload_image_via_form()
    {
        $file = UploadedFile::fake()->image('test-image.jpg', 800, 600)->size(1000);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.images.store'), [
                'image' => $file,
                'title' => 'Test Image',
                'category' => 'electronics',
                'brand' => 'Sony',
            ]);

        $response->assertRedirect(route('admin.images.index'))
            ->assertSessionHas('success');

        // Проверяем что job был добавлен в очередь
        Queue::assertPushed(ProcessImageUpload::class);
    }

    #[Test]
    public function it_validates_image_upload_required_fields()
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.images.store'), [
                // Пропускаем обязательные поля
            ]);

        $response->assertSessionHasErrors(['image', 'title', 'category', 'brand']);
    }

    #[Test]
    public function it_validates_image_file_type()
    {
        $invalidFile = UploadedFile::fake()->create('document.pdf', 1000);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.images.store'), [
                'image' => $invalidFile,
                'title' => 'Test Image',
                'category' => 'electronics',
                'brand' => 'Sony',
            ]);

        $response->assertSessionHasErrors(['image']);
    }

    #[Test]
    public function it_validates_image_size()
    {
        $largeFile = UploadedFile::fake()->image('large-image.jpg')->size(15000); // 15MB

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.images.store'), [
                'image' => $largeFile,
                'title' => 'Test Image',
                'category' => 'electronics',
                'brand' => 'Sony',
            ]);

        $response->assertSessionHasErrors(['image']);
    }
}
