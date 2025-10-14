<?php
// app/Http/Controllers/Api/ImageController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class ImageController extends Controller
{
    public function show(Request $request, string $id)
    {
        $params = $request->validate([
            'w' => 'nullable|integer|min:1|max:2000',
            'h' => 'nullable|integer|min:1|max:2000',
            'q' => 'nullable|integer|min:1|max:100'
        ]);

        $image = Image::where('is_active', true)->findOrFail($id);

        // Увеличиваем счетчик запросов
        /** @var \App\Models\User $user */
        $user = $request->attributes->get('api_user');
        $user->incrementRequestCount();

        // Ключ для кэша
        $cacheKey = "image:{$id}:" . md5(serialize($params));

        return Cache::remember($cacheKey, 3600, function () use ($image, $params) {
        
            $imageContent = Storage::get($image->file_path);

            // Проверяем, что данные не пустые
            if (empty($imageContent)) {
                abort(404, 'Image not found');
            }
            
            // Если нет параметров - возвращаем оригинал
            if (empty($params['w']) && empty($params['h'])) {
                return response($imageContent)
                    ->header('Content-Type', $image->mime_type)
                    ->header('Content-Disposition', 'inline; filename="' . $image->original_filename . '"');
            }

            // Обрабатываем изображение
            $processedImage = \Intervention\Image\ImageManager::gd()->read($imageContent);
            
            if (!empty($params['w']) || !empty($params['h'])) {
                $processedImage->resize($params['w'], $params['h'], function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            $quality = $params['q'] ?? 90;
            $format = $this->getFormat($image->mime_type);

            // AutoEncoder автоматически определяет лучший формат
            return response($processedImage->encode(new \Intervention\Image\Encoders\AutoEncoder($quality)))
                ->header('Content-Type', $image->mime_type)
                ->header('Content-Disposition', 'inline; filename="' . $image->original_filename . '"');
                    });
                }

    private function getFormat(string $mimeType): string
    {
        return match($mimeType) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'jpg'
        };
    }

    private function getContentType(string $format): string
    {
        return match($format) {
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg'
        };
    }
}
