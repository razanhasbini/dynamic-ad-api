<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class OlxApiService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.olx.base_url');
    }

    public function getCategories(): array
    {
        return Cache::remember(
            'olx.categories',
            now()->addDay(),
            function () {
                $response = Http::get(
                    "{$this->baseUrl}/categories"
                );

                $response->throw();

                return $response->json();
            }
        );
    }

    public function getCategoryFields(string $externalCategoryId): array
    {
        return Cache::remember(
            "olx.category_fields.{$externalCategoryId}",
            now()->addDay(),
            function () use ($externalCategoryId) {
                $response = Http::get(
                    "{$this->baseUrl}/categoryFields",
                    [
                        'categoryExternalIDs' => $externalCategoryId,
                        'includeWithoutCategory' => 'true',
                        'splitByCategoryIDs' => 'true',
                        'flatChoices' => 'true',
                        'groupChoicesBySection' => 'true',
                        'flat' => 'true',
                    ]
                );

                $response->throw();

                return $response->json();
            }
        );
    }
}
