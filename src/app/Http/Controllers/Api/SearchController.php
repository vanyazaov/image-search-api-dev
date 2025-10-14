<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ElasticSearchService;
use Illuminate\Http\Request;
use App\Models\User;

class SearchController extends Controller
{
    public function __construct(private ElasticSearchService $elasticSearch) {}

    public function search(Request $request)
    {
        $params = $request->validate([
            'q' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        $params['page'] = $params['page'] ?? 1;
        $params['limit'] = $params['limit'] ?? 20;

        $results = $this->elasticSearch->search($params);

        // Увеличиваем счетчик запросов
        /** @var User $user */
        $user = $request->attributes->get('api_user');
        $user->incrementRequestCount();

        $formattedResults = collect($results['hits']['hits'])->map(function ($hit) {
            $source = $hit['_source'];
            return [
                'id' => $source['id'],
                'title' => $source['title'],
                'category' => $source['category'],
                'brand' => $source['brand'],
                'original_filename' => $source['original_filename'],
                'urls' => [
                    'original' => route('api.images.show', $source['id']),
                    'thumbnail' => route('api.images.show', [
                        'image' => $source['id'],
                        'w' => 150,
                        'h' => 150
                    ])
                ]
            ];
        });

        return response()->json([
            'results' => $formattedResults,
            'total' => $results['hits']['total']['value'],
            'page' => (int) $params['page'],
            'limit' => (int) $params['limit']
        ]);
    }
}
