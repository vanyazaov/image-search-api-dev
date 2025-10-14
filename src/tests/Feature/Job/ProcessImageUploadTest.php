<?php
// tests/Feature/Job/ProcessImageUploadTest.php

namespace Tests\Feature\Job;

use App\Jobs\ProcessImageUpload;
use App\Jobs\IndexImageInElasticsearch;
use App\Models\Image;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Mockery;
use Mockery\MockInterface;

class ProcessImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        
        Queue::fake();
        
    }

    #[Test]
    public function it_processes_image_upload_and_creates_thumbnail()
    {
        // Создаем временный файл для теста
        $tempFile = tempnam(sys_get_temp_dir(), 'test_image');
        $image = imagecreatetruecolor(800, 600);
        imagejpeg($image, $tempFile, 90);
        imagedestroy($image);

        $imageData = [
            'original_filename' => 'test-image.jpg',
            'title' => 'Test Image',
            'category' => 'electronics',
            'brand' => 'Sony',
            'meta' => ['color' => 'black']
        ];

        $job = new ProcessImageUpload($tempFile, $imageData);
        $job->handle();

        // Проверяем что изображение создано в БД
        $this->assertDatabaseHas('images', [
            'title' => 'Test Image',
            'category' => 'electronics',
            'brand' => 'Sony',
        ]);

        $image = Image::first();

        // Проверяем что файл сохранен
        Storage::assertExists($image->file_path);

        // Проверяем что thumbnail создан
        Storage::assertExists('thumbnails/' . $image->id . '.jpg');

        // Проверяем что запущена индексация
        Queue::assertPushed(IndexImageInElasticsearch::class, function ($job) use ($image) {
            return $job->image->id === $image->id;
        });

        // Убедимся что временный файл удален
        $this->assertFalse(file_exists($tempFile));
    }

    #[Test]
    public function it_handles_image_processing_errors_gracefully()
    {
        // Создаем невалидный файл
        $invalidFile = tempnam(sys_get_temp_dir(), 'invalid');
        file_put_contents($invalidFile, 'invalid image data');

        $imageData = [
            'original_filename' => 'test.jpg',
            'title' => 'Test Image',
            'category' => 'test',
            'brand' => 'test',
        ];

        $job = new ProcessImageUpload($invalidFile, $imageData);
        
        // Job должен завершиться без фатальной ошибки
        $job->handle();

        // Проверяем что запись в БД не создана для невалидного изображения
        $this->assertDatabaseCount('images', 0);
    }
}
