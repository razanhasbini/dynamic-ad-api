<?php

namespace Tests\Feature;

use App\Services\OlxApiService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class OlxApiServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.olx.base_url' => 'https://olx.test/api']);
        Cache::flush();
        Sleep::fake();
    }

    public function test_it_retries_a_server_error_and_returns_the_next_response(): void
    {
        Http::fake([
            'olx.test/*' => Http::sequence()
                ->push('temporarily unavailable', 503)
                ->push([['id' => 1, 'name' => 'Cars']], 200),
        ]);

        $categories = app(OlxApiService::class)->getCategories();

        $this->assertSame([['id' => 1, 'name' => 'Cars']], $categories);
        Http::assertSentCount(2);
    }

    public function test_it_does_not_retry_a_client_error(): void
    {
        Http::fake(['olx.test/*' => Http::response('not found', 404)]);

        try {
            app(OlxApiService::class)->getCategories();
            $this->fail('Expected a RequestException for a 404 response.');
        } catch (RequestException $exception) {
            $this->assertSame(404, $exception->response->status());
        }

        Http::assertSentCount(1);
    }
}
