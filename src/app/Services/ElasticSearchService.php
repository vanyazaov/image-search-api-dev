<?php

namespace App\Services;

use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Log;

class ElasticSearchService
{
    protected $client;

    public function __construct()
    {
        $this->client = ClientBuilder::create()
            ->setHosts([config('elasticsearch.hosts')])
            ->setBasicAuthentication(
                config('elasticsearch.username'),
                config('elasticsearch.password')
            )
            ->build();
    }
    
    public function createIndex(): void
    {
        try {
            $params = [
                'index' => 'images',
                'body' => [
                    'settings' => [
                        'number_of_shards' => 1,
                        'number_of_replicas' => 0,
                        'analysis' => [
                            'analyzer' => [
                                'default' => [
                                    'type' => 'standard'
                                ]
                            ]
                        ]
                    ],
                    'mappings' => [
                        'properties' => [
                            'id' => ['type' => 'keyword'],
                            'title' => [
                                'type' => 'text',
                                'analyzer' => 'standard',
                                'fields' => [
                                    'keyword' => ['type' => 'keyword']
                                ]
                            ],
                            'category' => ['type' => 'keyword'],
                            'brand' => ['type' => 'keyword'],
                            'original_filename' => ['type' => 'text'],
                            'is_active' => ['type' => 'boolean'],
                            'created_at' => ['type' => 'date']
                        ]
                    ]
                ]
            ];

            if (!$this->client->indices()->exists(['index' => 'images'])->asBool()) {
                $this->client->indices()->create($params);
                Log::info('ElasticSearch index "images" created successfully');
            } else {
                Log::info('ElasticSearch index "images" already exists');
            }
        } catch (\Exception $e) {
            Log::error('ElasticSearch create index failed: ' . $e->getMessage());
            throw $e;
        }
    }     

    public function indexImage(array $imageData): void
    {
        try {
            $this->client->index([
                'index' => 'images',
                'id' => $imageData['id'],
                'body' => $imageData
            ]);
        } catch (\Exception $e) {
            Log::error('ElasticSearch indexing failed: ' . $e->getMessage());
        }
    }

    public function search(array $params): array
    {
        $searchParams = [
            'index' => 'images',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => []
                    ]
                ],
                'from' => ($params['page'] - 1) * $params['limit'],
                'size' => $params['limit']
            ]
        ];

        // Поиск по названию
        if (!empty($params['q'])) {
            $searchParams['body']['query']['bool']['must'][] = [
                'multi_match' => [
                    'query' => $params['q'],
                    'fields' => ['title', 'original_filename', 'brand', 'category']
                ]
            ];
        }

        // Фильтр по категории
        if (!empty($params['category'])) {
            $searchParams['body']['query']['bool']['must'][] = [
                'term' => ['category' => $params['category']]
            ];
        }

        // Фильтр по бренду
        if (!empty($params['brand'])) {
            $searchParams['body']['query']['bool']['must'][] = [
                'term' => ['brand' => $params['brand']]
            ];
        }

        try {
            $response = $this->client->search($searchParams);
            return $response->asArray();
        } catch (\Exception $e) {
            Log::error('ElasticSearch search failed: ' . $e->getMessage());
            return ['hits' => ['hits' => [], 'total' => ['value' => 0]]];
        }
    }

    public function deleteImage(string $imageId): void
    {
        try {
            $this->client->delete([
                'index' => 'images',
                'id' => $imageId
            ]);
        } catch (\Exception $e) {
            Log::error('ElasticSearch delete failed: ' . $e->getMessage());
        }
    }
}
