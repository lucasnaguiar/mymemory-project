<?php

use App\Models\Memo;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeCrudUser(): User
{
    $plan = SubscriptionPlan::create([
        'name' => 'CRUD Plan', 'type' => 'individual', 'is_active' => true,
        'storage_gb' => 5, 'max_memos' => null, 'price_cents' => 0,
    ]);
    return User::factory()->create(['subscription_plan_id' => $plan->id]);
}

function makeCrudMemo(User $user, array $attrs = []): Memo
{
    return Memo::create(array_merge([
        'user_id'  => $user->id,
        'group_id' => null,
        'type'     => 'text',
        'status'   => 'confirmed',
        'content'  => 'Test content',
        'summary'  => 'Test summary',
        'ai_level' => 'none',
    ], $attrs));
}

// ---------------------------------------------------------------------------
// GET /api/v1/memos/{id}
// ---------------------------------------------------------------------------

test('owner can view own memo', function () {
    $user = makeCrudUser();
    $memo = makeCrudMemo($user, ['summary' => 'My memo']);

    $this->actingAs($user)
        ->getJson("/api/v1/memos/{$memo->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $memo->id)
        ->assertJsonPath('data.summary', 'My memo');
});

test('other user cannot view personal memo', function () {
    $owner = makeCrudUser();
    $other = makeCrudUser();
    $memo  = makeCrudMemo($owner);

    $this->actingAs($other)
        ->getJson("/api/v1/memos/{$memo->id}")
        ->assertStatus(403);
});

test('show returns 404 for nonexistent memo', function () {
    $user = makeCrudUser();
    $this->actingAs($user)->getJson('/api/v1/memos/99999')->assertStatus(404);
});

// ---------------------------------------------------------------------------
// PATCH /api/v1/memos/{id}
// ---------------------------------------------------------------------------

test('owner can update memo', function () {
    $user = makeCrudUser();
    $memo = makeCrudMemo($user);

    $this->actingAs($user)
        ->patchJson("/api/v1/memos/{$memo->id}", [
            'summary'  => 'Updated summary',
            'keywords' => ['new', 'keywords'],
        ])
        ->assertOk()
        ->assertJsonPath('data.summary', 'Updated summary');

    expect($memo->fresh()->keywords)->toEqual(['new', 'keywords']);
});

test('other user cannot update memo', function () {
    $owner = makeCrudUser();
    $other = makeCrudUser();
    $memo  = makeCrudMemo($owner);

    $this->actingAs($other)
        ->patchJson("/api/v1/memos/{$memo->id}", ['summary' => 'Hacked'])
        ->assertStatus(403);
});

test('update returns 404 for nonexistent memo', function () {
    $user = makeCrudUser();
    $this->actingAs($user)->patchJson('/api/v1/memos/99999', ['summary' => 'x'])->assertStatus(404);
});

// ---------------------------------------------------------------------------
// DELETE /api/v1/memos/{id}
// ---------------------------------------------------------------------------

test('owner can soft-delete memo', function () {
    $user = makeCrudUser();
    $memo = makeCrudMemo($user);

    $this->actingAs($user)
        ->deleteJson("/api/v1/memos/{$memo->id}")
        ->assertNoContent();

    expect(Memo::find($memo->id))->toBeNull();
    expect(Memo::withTrashed()->find($memo->id))->not->toBeNull();
});

test('other user cannot delete memo', function () {
    $owner = makeCrudUser();
    $other = makeCrudUser();
    $memo  = makeCrudMemo($owner);

    $this->actingAs($other)
        ->deleteJson("/api/v1/memos/{$memo->id}")
        ->assertStatus(403);
});

test('guest cannot delete memo', function () {
    $user = makeCrudUser();
    $memo = makeCrudMemo($user);
    $this->deleteJson("/api/v1/memos/{$memo->id}")->assertStatus(401);
});

// ---------------------------------------------------------------------------
// Synonyms
// ---------------------------------------------------------------------------

test('synonyms endpoint returns array', function () {
    $user = makeCrudUser();
    $this->actingAs($user)
        ->postJson('/api/v1/memos/search/synonyms', ['query' => 'laravel'])
        ->assertOk()
        ->assertJsonStructure(['data' => ['synonyms']]);
});
