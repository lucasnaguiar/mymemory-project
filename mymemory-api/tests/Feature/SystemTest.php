<?php

/**
 * Suite de testes críticos de sistema — Etapa 10
 *
 * Cobre os fluxos principais: auth → memo texto → busca → grupos → admin
 * de ponta a ponta, garantindo que as camadas críticas interajam correctamente.
 */

use App\Models\Group;
use App\Models\Memo;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makePlan(string $name = 'Plano Teste', string $type = 'individual'): SubscriptionPlan
{
    return SubscriptionPlan::create([
        'name'       => $name,
        'type'       => $type,
        'is_active'  => true,
        'storage_gb' => 10,
        'price_cents'=> 0,
    ]);
}

function makeUser(SubscriptionPlan $plan, string $role = 'user'): User
{
    return User::factory()->create([
        'subscription_plan_id' => $plan->id,
        'role'                 => $role,
    ]);
}

// ---------------------------------------------------------------------------
// 1. Auth flow
// ---------------------------------------------------------------------------

test('unauthenticated requests to protected endpoints return 401', function () {
    $this->getJson('/api/v1/me')->assertStatus(401);
    $this->getJson('/api/v1/memos/recent')->assertStatus(401);
    $this->getJson('/api/v1/admin/subscription-plans')->assertStatus(401);
});

test('authenticated user can fetch own profile', function () {
    $plan = makePlan();
    $user = makeUser($plan);

    $this->actingAs($user)
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonPath('data.role', 'user');
});

// ---------------------------------------------------------------------------
// 2. Memo texto: create → show → update → delete
// ---------------------------------------------------------------------------

test('user can create a text memo and retrieve it', function () {
    $plan = makePlan();
    $user = makeUser($plan);

    $payload = [
        'content'  => 'Nota de teste para o sistema.',
        'ai_level' => 'none',
        'group_id' => null,
    ];

    $create = $this->actingAs($user)
        ->postJson('/api/v1/memos/text', $payload)
        ->assertStatus(202);

    $memoId = $create->json('data.id');
    expect($memoId)->toBeInt();

    $this->actingAs($user)
        ->getJson("/api/v1/memos/{$memoId}")
        ->assertOk()
        ->assertJsonPath('data.id', $memoId);
});

test('user can update a text memo', function () {
    $plan = makePlan();
    $user = makeUser($plan);

    $create = $this->actingAs($user)
        ->postJson('/api/v1/memos/text', ['content' => 'Original', 'ai_level' => 'none'])
        ->assertStatus(202);

    $id = $create->json('data.id');

    $this->actingAs($user)
        ->patchJson("/api/v1/memos/{$id}", ['title' => 'Título atualizado'])
        ->assertOk()
        ->assertJsonPath('data.title', 'Título atualizado');
});

test('user can soft-delete a text memo', function () {
    $plan = makePlan();
    $user = makeUser($plan);

    $create = $this->actingAs($user)
        ->postJson('/api/v1/memos/text', ['content' => 'Para excluir', 'ai_level' => 'none'])
        ->assertStatus(202);

    $id = $create->json('data.id');

    $this->actingAs($user)
        ->deleteJson("/api/v1/memos/{$id}")
        ->assertNoContent();

    expect(Memo::find($id))->toBeNull();
    expect(Memo::withTrashed()->find($id))->not->toBeNull();
});

test('user cannot delete memo owned by another user', function () {
    $plan   = makePlan();
    $owner  = makeUser($plan);
    $other  = makeUser($plan);

    $create = $this->actingAs($owner)
        ->postJson('/api/v1/memos/text', ['content' => 'Privado', 'ai_level' => 'none'])
        ->assertStatus(202);

    $id = $create->json('data.id');

    $this->actingAs($other)
        ->deleteJson("/api/v1/memos/{$id}")
        ->assertStatus(403);
});

// ---------------------------------------------------------------------------
// 3. Busca de memos
// ---------------------------------------------------------------------------

test('search returns own memos and respects user isolation', function () {
    $plan  = makePlan();
    $alice = makeUser($plan);
    $bob   = makeUser($plan);

    // Alice creates a memo
    $this->actingAs($alice)
        ->postJson('/api/v1/memos/text', ['content' => 'laranja banana', 'ai_level' => 'none']);

    // Bob creates a memo
    $this->actingAs($bob)
        ->postJson('/api/v1/memos/text', ['content' => 'laranja maçã', 'ai_level' => 'none']);

    // Alice searches — should only see her own memo
    $alice_res = $this->actingAs($alice)
        ->getJson('/api/v1/memos/search?q=laranja')
        ->assertOk();

    $ids = collect($alice_res->json('data.items'))->pluck('user_id')->unique()->values();
    expect($ids)->toEqual(collect([$alice->id]));
});

test('recent memos endpoint returns only user memos', function () {
    $plan  = makePlan();
    $alice = makeUser($plan);
    $bob   = makeUser($plan);

    $this->actingAs($alice)
        ->postJson('/api/v1/memos/text', ['content' => 'Memo da Alice', 'ai_level' => 'none']);

    $this->actingAs($bob)
        ->postJson('/api/v1/memos/text', ['content' => 'Memo do Bob', 'ai_level' => 'none']);

    $res = $this->actingAs($alice)
        ->getJson('/api/v1/memos/recent')
        ->assertOk();

    $userIds = collect($res->json('data.items'))->pluck('user_id')->unique()->values();
    expect($userIds)->toEqual(collect([$alice->id]));
});

// ---------------------------------------------------------------------------
// 4. Grupos: criar → painel de dono → convidar
// ---------------------------------------------------------------------------

test('user can create a group with a valid plan', function () {
    $indivPlan = makePlan();
    $groupPlan = makePlan('Plano Grupo', 'group');
    $user      = makeUser($indivPlan);

    $this->actingAs($user)
        ->postJson('/api/v1/groups', [
            'name'                 => 'Grupo Teste',
            'subscription_plan_id' => $groupPlan->id,
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.name', 'Grupo Teste');
});

test('group owner can view owner panel', function () {
    $indivPlan = makePlan();
    $groupPlan = makePlan('Plano Grupo', 'group');
    $owner     = makeUser($indivPlan);

    $createRes = $this->actingAs($owner)
        ->postJson('/api/v1/groups', [
            'name'                 => 'Meu Grupo',
            'subscription_plan_id' => $groupPlan->id,
        ])
        ->assertStatus(201);

    $groupId = $createRes->json('data.id');

    $this->actingAs($owner)
        ->getJson("/api/v1/groups/{$groupId}/owner-panel")
        ->assertOk()
        ->assertJsonStructure(['data' => ['group', 'members', 'invites']]);
});

test('non-owner cannot access owner panel', function () {
    $indivPlan = makePlan();
    $groupPlan = makePlan('Plano Grupo', 'group');
    $owner     = makeUser($indivPlan);
    $other     = makeUser($indivPlan);

    $createRes = $this->actingAs($owner)
        ->postJson('/api/v1/groups', [
            'name'                 => 'Grupo Privado',
            'subscription_plan_id' => $groupPlan->id,
        ]);

    $groupId = $createRes->json('data.id');

    $this->actingAs($other)
        ->getJson("/api/v1/groups/{$groupId}/owner-panel")
        ->assertStatus(403);
});

// ---------------------------------------------------------------------------
// 5. Admin: acesso restrito + CRUD de planos
// ---------------------------------------------------------------------------

test('admin can manage subscription plans end-to-end', function () {
    $adminPlan = makePlan();
    $admin     = makeUser($adminPlan, 'admin');

    // Create
    $created = $this->actingAs($admin)
        ->postJson('/api/v1/admin/subscription-plans', [
            'name'       => 'Plano Novo',
            'type'       => 'individual',
            'storage_gb' => 5,
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Plano Novo');

    $planId = $created->json('data.id');

    // Update
    $this->actingAs($admin)
        ->patchJson("/api/v1/admin/subscription-plans/{$planId}", [
            'name'       => 'Plano Atualizado',
            'type'       => 'individual',
            'storage_gb' => 10,
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Plano Atualizado');

    // Delete (no subscribers)
    $this->actingAs($admin)
        ->deleteJson("/api/v1/admin/subscription-plans/{$planId}")
        ->assertNoContent();

    expect(SubscriptionPlan::find($planId))->toBeNull();
});

// ---------------------------------------------------------------------------
// 6. Security headers
// ---------------------------------------------------------------------------

test('api responses include security headers', function () {
    $this->getJson('/api/v1/health')
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});
