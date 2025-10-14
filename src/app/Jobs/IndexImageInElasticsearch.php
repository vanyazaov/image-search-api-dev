<?php
// app/Jobs/IndexImageInElasticsearch.php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Image;
use App\Services\ElasticSearchService;

class IndexImageInElasticsearch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
   

    public function __construct(public Image $image) {}

    public function handle(ElasticSearchService $elasticSearch): void
    {
        // Проверяем что изображение активно перед индексацией
        if (!$this->image->is_active) {
            \Log::info("Skipping indexing for inactive image: {$this->image->id}");
            return;
        }
        try {
            $elasticSearch->indexImage($this->image->getSearchableData());
            \Log::info("Successfully indexed image in ElasticSearch: {$this->image->id}");
        } catch (\Exception $e) {
            \Log::error("Failed to index image in ElasticSearch: {$this->image->id}", [
                'error' => $e->getMessage(),
                'image_id' => $this->image->id,
                'title' => $this->image->title
            ]);
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
