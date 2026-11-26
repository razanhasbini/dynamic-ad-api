<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
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
                $response = $this->client()->get(
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
                $response = $this->client()->get(
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

    /**
     * OLX is an external dependency: bound every call with a timeout and retry
     * transient failures (connection errors and 5xx) a couple of times before
     * the caller's ->throw() surfaces the error.
     */
    private function client(): PendingRequest
    {
        return Http::acceptJson()
            ->timeout(15)
            ->connectTimeout(5)
            ->retry(2, 500, fn ($exception) => $exception instanceof ConnectionException
                || ($exception instanceof RequestException && $exception->response->serverError()), throw: false);
    }
}
