<?php

use App\Models\Memo;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeQueryUser(): User
{
    $plan = SubscriptionPlan::create([
        'name' => 'Plan', 'type' => 'individual', 'is_active' => true,
        'storage_gb' => 5, 'max_memos' => null, 'price_cents' => 0,
    ]);
    return User::factory()->create(['subscription_plan_id' => $plan->id]);
}

function makeMemo(User $user, array $attrs = []): Memo
{
    return Memo::create(array_merge([
        'user_id'  => $user->id,
        'group_id' => null,
        'type'     => 'text',
        'status'   => 'confirmed',
        'content'  => 'Default content',
        'summary'  => 'Default summary',
        'ai_level' => 'none',
    ], $attrs));
}

// ---------------------------------------------------------------------------
// GET /api/v1/memos/recent
// ---------------------------------------------------------------------------

test('guest cannot list recent memos', function () {
    $this->getJson('/api/v1/memos/recent')->assertStatus(401);
});

test('recent returns confirmed memos of user', function () {
    $user = makeQueryUser();
    makeMemo($user, ['summary' => 'First memo']);
    makeMemo($user, ['summary' => 'Second memo']);

    $res = $this->actingAs($user)->getJson('/api/v1/memos/recent')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['total']]);

    expect(count($res->json('data')))->toBe(2);
});

test('recent does not return drafts', function () {
    $user = makeQueryUser();
    makeMemo($user, ['status' => 'draft', 'summary' => 'Draft']);
    makeMemo($user, ['status' => 'confirmed', 'summary' => 'Confirmed']);

    $res = $this->actingAs($user)->getJson('/api/v1/memos/recent')->assertOk();
    expect(count($res->json('data')))->toBe(1);
});

test('recent does not return other users memos', function () {
    $user1 = makeQueryUser();
    $user2 = makeQueryUser();
    makeMemo($user1);
    makeMemo($user2);

    $res = $this->actingAs($user1)->getJson('/api/v1/memos/recent')->assertOk();
    expect(count($res->json('data')))->toBe(1);
    expect($res->json('data.0.user_id'))->toBe($user1->id);
});

test('recent respects limit param', function () {
    $user = makeQueryUser();
    for ($i = 0; $i < 5; $i++) makeMemo($user);

    $res = $this->actingAs($user)->getJson('/api/v1/memos/recent?limit=3')->assertOk();
    expect(count($res->json('data')))->toBe(3);
});

// ---------------------------------------------------------------------------
// POST /api/v1/memos/search
// ---------------------------------------------------------------------------

test('guest cannot search', function () {
    $this->postJson('/api/v1/memos/search', ['query' => 'foo'])->assertStatus(401);
});

test('search returns matching memos', function () {
    $user = makeQueryUser();
    makeMemo($user, ['summary' => 'Laravel framework rocks']);
    makeMemo($user, ['summary' => 'Vue is great']);

    $res = $this->actingAs($user)
        ->postJson('/api/v1/memos/search', ['query' => 'laravel'])
        ->assertOk();

    expect(count($res->json('data')))->toBe(1);
    expect($res->json('data.0.summary'))->toContain('Laravel');
});

test('search AND returns memos containing all terms', function () {
    $user = makeQueryUser();
    makeMemo($user, ['summary' => 'Laravel framework tutorial']);
    makeMemo($user, ['summary' => 'Laravel introduction']);

    $res = $this->actingAs($user)
        ->postJson('/api/v1/memos/search', ['query' => 'laravel tutorial', 'operator' => 'AND'])
        ->assertOk();

    expect(count($res->json('data')))->toBe(1);
});

test('search OR returns memos containing any term', function () {
    $user = makeQueryUser();
    makeMemo($user, ['summary' => 'Laravel framework']);
    makeMemo($user, ['summary' => 'Vue js tutorial']);

    $res = $this->actingAs($user)
        ->postJson('/api/v1/memos/search', ['query' => 'laravel vue', 'operator' => 'OR'])
        ->assertOk();

    expect(count($res->json('data')))->toBe(2);
    expect($res->json('meta.highlight_terms'))->toContain('laravel');
});

test('search returns 422 without query', function () {
    $user = makeQueryUser();
    $this->actingAs($user)->postJson('/api/v1/memos/search', [])->assertStatus(422);
});

test('search does not return other users memos', function () {
    $user1 = makeQueryUser();
    $user2 = makeQueryUser();
    makeMemo($user1, ['summary' => 'secret data']);
    makeMemo($user2, ['summary' => 'irrelevant']);

    $res = $this->actingAs($user2)
        ->postJson('/api/v1/memos/search', ['query' => 'secret'])
        ->assertOk();

    expect(count($res->json('data')))->toBe(0);
});

// ---------------------------------------------------------------------------
// GET /api/v1/memos/search/authors
// ---------------------------------------------------------------------------

test('authors returns users with accessible memos', function () {
    $user1 = makeQueryUser();
    makeMemo($user1, ['summary' => 'personal']);

    $res = $this->actingAs($user1)->getJson('/api/v1/memos/search/authors')->assertOk();
    $authorIds = array_column($res->json('data'), 'id');
    expect($authorIds)->toContain($user1->id);
});
