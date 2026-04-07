<?php

use App\Contracts\AiProviderInterface;
use App\DTOs\Memo\AiOutputDTO;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeUrlPlanAndUser(array $planAttrs = []): User
{
    $plan = SubscriptionPlan::create(array_merge([
        'name'                  => 'URL Test Plan',
        'type'                  => 'individual',
        'is_active'             => true,
        'storage_gb'            => 5,
        'max_memos'             => 50,
        'api_credits_per_month' => null,
        'downloads_per_month'   => null,
        'price_cents'           => 0,
    ], $planAttrs));

    return User::factory()->create(['subscription_plan_id' => $plan->id]);
}

function mockUrlAi(): void
{
    app()->bind(AiProviderInterface::class, function () {
        return new class implements AiProviderInterface {
            public function summarizeText(string $content, string $level): AiOutputDTO
            {
                return new AiOutputDTO('Text summary', ['text'], $content, 0.2);
            }

            public function extractAndSummarizeUrl(string $url, string $level): AiOutputDTO
            {
                return new AiOutputDTO('URL summary', ['url', 'test'], "Extracted from {$url}", 0.3);
            }
        };
    });
}

// ---------------------------------------------------------------------------
// POST /api/v1/memos/url/process
// ---------------------------------------------------------------------------

test('guest cannot process url memo', function () {
    $this->postJson('/api/v1/memos/url/process', ['url' => 'https://example.com'])
        ->assertStatus(401);
});

test('process url returns draft with ai suggestion', function () {
    mockUrlAi();
    $user = makeUrlPlanAndUser();

    $this->actingAs($user)
        ->postJson('/api/v1/memos/url/process', ['url' => 'https://example.com', 'ai_level' => 'full'])
        ->assertOk()
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.type', 'url')
        ->assertJsonPath('data.summary', 'URL summary')
        ->assertJsonPath('data.source_url', 'https://example.com');
});

test('process url requires valid url', function () {
    $user = makeUrlPlanAndUser();

    $this->actingAs($user)
        ->postJson('/api/v1/memos/url/process', ['url' => 'not-a-url'])
        ->assertStatus(422);
});

// ---------------------------------------------------------------------------
// POST /api/v1/memos/url/confirm
// ---------------------------------------------------------------------------

test('confirm url upgrades draft to confirmed', function () {
    mockUrlAi();
    $user = makeUrlPlanAndUser();

    $res    = $this->actingAs($user)->postJson('/api/v1/memos/url/process', ['url' => 'https://example.com']);
    $memoId = $res->json('data.id');

    $this->actingAs($user)
        ->postJson('/api/v1/memos/url/confirm', [
            'memo_id'  => $memoId,
            'title'    => 'Example Site',
            'summary'  => 'Custom summary',
            'keywords' => ['web'],
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonPath('data.title', 'Example Site');
});

test('cannot confirm url memo of another user', function () {
    mockUrlAi();
    $owner = makeUrlPlanAndUser();
    $other = makeUrlPlanAndUser();

    $res    = $this->actingAs($owner)->postJson('/api/v1/memos/url/process', ['url' => 'https://x.com']);
    $memoId = $res->json('data.id');

    $this->actingAs($other)
        ->postJson('/api/v1/memos/url/confirm', ['memo_id' => $memoId])
        ->assertStatus(403);
});

// ---------------------------------------------------------------------------
// POST /api/v1/memos/url (direct)
// ---------------------------------------------------------------------------

test('direct url creation returns confirmed memo', function () {
    mockUrlAi();
    $user = makeUrlPlanAndUser();

    $this->actingAs($user)
        ->postJson('/api/v1/memos/url', ['url' => 'https://laravel.com', 'ai_level' => 'full'])
        ->assertCreated()
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonPath('data.type', 'url')
        ->assertJsonPath('data.source_url', 'https://laravel.com');
});

test('direct url creation blocked when limit reached', function () {
    $user = makeUrlPlanAndUser(['max_memos' => 0]);

    $this->actingAs($user)
        ->postJson('/api/v1/memos/url', ['url' => 'https://example.com'])
        ->assertStatus(422);
});
