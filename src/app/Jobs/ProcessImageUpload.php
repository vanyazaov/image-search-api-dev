<?php
// app/Jobs/ProcessImageUpload.php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Image;
use Illuminate\Support\Facades\Storage;

class ProcessImageUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $filePath,
        public array $imageData
    ) {}

    public function handle(): void
    {
        try {
            // Пробуем открыть изображение
            $img = \Intervention\Image\ImageManager::gd()->read(file_get_contents($this->filePath));
        } catch (\Exception $e) {
            \Log::warning('Invalid image uploaded: ' . $this->filePath);
            if (file_exists($this->filePath)) {
                @unlink($this->filePath);
            }
            return; // <-- Прекращаем выполнение job
        }
        // Сохраняем оригинальное изображение
        $storagePath = 'images/' . date('Y/m');
        $filename = uniqid() . '.' . pathinfo($this->imageData['original_filename'], PATHINFO_EXTENSION);
        
        $fullPath = $storagePath . '/' . $filename;
        Storage::put($fullPath, file_get_contents($this->filePath));
        
        // Создаем запись в базе данных
        $image = Image::create([
            'original_filename' => $this->imageData['original_filename'],
            'file_path' => $fullPath,
            'file_size' => Storage::size($fullPath),
            'mime_type' => Storage::mimeType($fullPath),
            'title' => $this->imageData['title'],
            'category' => $this->imageData['category'],
            'brand' => $this->imageData['brand'],
            'meta' => $this->imageData['meta'] ?? []
        ]);

        // Создаем thumbnail
        $this->createThumbnail($fullPath, $image->id);

        // Запускаем индексацию в ElasticSearch
        IndexImageInElasticsearch::dispatch($image);

        // Удаляем временный файл
        if (file_exists($this->filePath)) {
            @unlink($this->filePath);
        }
    }
    private function createThumbnail(string $imagePath, string $imageId): void
    {
        try {
            $image = \Intervention\Image\ImageManager::gd()->read(Storage::get($imagePath));

            $image->resize(300, 300, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            $thumbnailPath = 'thumbnails/' . $imageId . '.jpg';
            $quality = 80;

            if (!Storage::exists('thumbnails')) {
                Storage::makeDirectory('thumbnails');
            }

            Storage::put($thumbnailPath, $image->encode(new \Intervention\Image\Encoders\AutoEncoder($quality)));

        } catch (\Exception $e) {
            \Log::error('Thumbnail creation failed: ' . $e->getMessage());
        }
    }
    
    public function failed(\Throwable $exception)
    {
        \Log::error("Image processing failed: {$this->image->id}", [
            'error' => $exception->getMessage()
        ]);
        
        // Можно отправить уведомление в Slack/Email
    }
}
