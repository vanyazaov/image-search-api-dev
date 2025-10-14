<?php
// app/Console/Commands/SetupSearchIndex.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ElasticSearchService;
use App\Models\Image;

class SetupSearchIndex extends Command
{
    protected $signature = 'search:setup';
    protected $description = 'Create ElasticSearch index and reindex all images';

    public function handle(ElasticSearchService $elasticSearch): void
    {
        $this->info('Creating ElasticSearch index...');
        $elasticSearch->createIndex();

        $this->info('Indexing existing images...');
        $images = Image::where('is_active', true)->get();

        $bar = $this->output->createProgressBar($images->count());
        
        foreach ($images as $image) {
            $elasticSearch->indexImage($image->getSearchableData());
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Search index setup completed!');
    }
}
