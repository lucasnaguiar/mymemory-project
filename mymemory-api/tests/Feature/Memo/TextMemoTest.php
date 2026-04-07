<?php

use App\Contracts\AiProviderInterface;
use App\DTOs\Memo\AiOutputDTO;
use App\Models\Memo;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makePlanAndUser(array $planAttrs = [], array $userAttrs = []): User
{
    $plan = SubscriptionPlan::create(array_merge([
        'name'                  => 'Test',
        'type'                  => 'individual',
        'is_active'             => true,
        'storage_gb'            => 5,
        'max_memos'             => 50,
        'api_credits_per_month' => null,
        'downloads_per_month'   => null,
        'price_cents'           => 0,
    ], $planAttrs));

    return User::factory()->create(array_merge(['subscription_plan_id' => $plan->id], $userAttrs));
}

function mockAi(): void
{
    app()->bind(AiProviderInterface::class, function () {
        return new class implements AiProviderInterface {
            public function summarizeText(string $content, string $level): AiOutputDTO
            {
                return new AiOutputDTO('Mock summary', ['mock', 'keyword'], $content, 0.5);
            }

            public function extractAndSummarizeUrl(string $url, string $level): AiOutputDTO
            {
                return new AiOutputDTO('Mock url summary', ['url', 'mock'], "content of {$url}", 0.5);
            }
        };
    });
}

// ---------------------------------------------------------------------------
// POST /api/v1/memos/text/process
// ---------------------------------------------------------------------------

test('guest cannot process text memo', function () {
    $this->postJson('/api/v1/memos/text/process', ['content' => 'Hello'])
        ->assertStatus(401);
});

test('process text returns draft memo with ai suggestion', function () {
    mockAi();
    $user = makePlanAndUser();

    $this->actingAs($user)
        ->postJson('/api/v1/memos/text/process', ['content' => 'My text content', 'ai_level' => 'full'])
        ->assertOk()
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.type', 'text')
        ->assertJsonPath('data.summary', 'Mock summary')
        ->assertJsonStructure(['data' => ['id', 'status', 'content', 'summary', 'keywords']]);
});

test('process text with ai_level none skips ai call', function () {
    $user = makePlanAndUser(); // no mock needed — StubAiProvider returns empty for 'none'

    $response = $this->actingAs($user)
        ->postJson('/api/v1/memos/text/process', ['content' => 'Hello', 'ai_level' => 'none'])
        ->assertOk();

    expect($response->json('data.summary'))->toBeNull();
});

test('process text validation requires content', function () {
    $user = makePlanAndUser();

    $this->actingAs($user)
        ->postJson('/api/v1/memos/text/process', [])
        ->assertStatus(422)
        ->assertJsonPath('error', true);
});

// ---------------------------------------------------------------------------
// POST /api/v1/memos/text/confirm
// ---------------------------------------------------------------------------

test('confirm upgrades draft to confirmed memo', function () {
    mockAi();
    $user = makePlanAndUser();

    // Create draft first
    $processRes = $this->actingAs($user)
        ->postJson('/api/v1/memos/text/process', ['content' => 'Hello world']);
    $memoId = $processRes->json('data.id');

    $this->actingAs($user)
        ->postJson('/api/v1/memos/text/confirm', [
            'memo_id'  => $memoId,
            'content'  => 'Edited content',
            'summary'  => 'My summary',
            'keywords' => ['foo', 'bar'],
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonPath('data.summary', 'My summary');
});

test('confirm rejects draft belonging to another user', function () {
    mockAi();
    $owner = makePlanAndUser();
    $other = makePlanAndUser();

    $res    = $this->actingAs($owner)->postJson('/api/v1/memos/text/process', ['content' => 'X']);
    $memoId = $res->json('data.id');

    $this->actingAs($other)
        ->postJson('/api/v1/memos/text/confirm', ['memo_id' => $memoId, 'content' => 'X'])
        ->assertStatus(403);
});

test('confirm is blocked when plan memo limit is reached', function () {
    mockAi();
    $user = makePlanAndUser(['max_memos' => 0]); // limit = 0

    $res    = $this->actingAs($user)->postJson('/api/v1/memos/text/process', ['content' => 'Hi']);
    $memoId = $res->json('data.id');

    $this->actingAs($user)
        ->postJson('/api/v1/memos/text/confirm', ['memo_id' => $memoId, 'content' => 'Hi'])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'memo_limit_reached');
});

// ---------------------------------------------------------------------------
// POST /api/v1/memos/text (direct creation)
// ---------------------------------------------------------------------------

test('direct text creation returns confirmed memo', function () {
    mockAi();
    $user = makePlanAndUser();

    $this->actingAs($user)
        ->postJson('/api/v1/memos/text', ['content' => 'Direct content', 'ai_level' => 'basic'])
        ->assertCreated()
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonPath('data.type', 'text');
});

test('direct creation is blocked when limit reached', function () {
    $user = makePlanAndUser(['max_memos' => 0]);

    $this->actingAs($user)
        ->postJson('/api/v1/memos/text', ['content' => 'X'])
        ->assertStatus(422);
});
