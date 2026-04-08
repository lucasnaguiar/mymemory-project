<?php

use App\Models\Group;
use App\Models\GroupInvite;
use App\Models\GroupMember;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeTestUser(): User
{
    $plan = SubscriptionPlan::create([
        'name' => 'Individual', 'type' => 'individual', 'is_active' => true,
        'storage_gb' => 5, 'max_memos' => 50, 'price_cents' => 0,
    ]);
    return User::factory()->create(['subscription_plan_id' => $plan->id]);
}

function makeTestGroupPlan(): SubscriptionPlan
{
    return SubscriptionPlan::create([
        'name' => 'Group Plan', 'type' => 'group', 'is_active' => true,
        'storage_gb' => 20, 'max_memos' => 200, 'price_cents' => 0,
    ]);
}

function makeTestGroup(User $owner, SubscriptionPlan $plan): Group
{
    return Group::create([
        'name'                 => 'Test Group',
        'owner_user_id'        => $owner->id,
        'subscription_plan_id' => $plan->id,
    ]);
}

// ---------------------------------------------------------------------------
// POST /api/v1/groups
// ---------------------------------------------------------------------------

test('guest cannot create group', function () {
    $plan = makeTestGroupPlan();
    $this->postJson('/api/v1/groups', ['name' => 'My Group', 'subscription_plan_id' => $plan->id])
        ->assertStatus(401);
});

test('user can create group with group plan', function () {
    $user = makeTestUser();
    $plan = makeTestGroupPlan();

    $res = $this->actingAs($user)
        ->postJson('/api/v1/groups', ['name' => 'My Group', 'subscription_plan_id' => $plan->id])
        ->assertCreated()
        ->assertJsonPath('data.name', 'My Group')
        ->assertJsonPath('data.owner_user_id', $user->id);

    expect(Group::find($res->json('data.id')))->not->toBeNull();
});

test('cannot create group with individual plan', function () {
    $user        = makeTestUser();
    $indivPlan   = SubscriptionPlan::first();

    $this->actingAs($user)
        ->postJson('/api/v1/groups', ['name' => 'My Group', 'subscription_plan_id' => $indivPlan->id])
        ->assertStatus(422);
});

test('create group validation requires name', function () {
    $user = makeTestUser();
    $plan = makeTestGroupPlan();
    $this->actingAs($user)
        ->postJson('/api/v1/groups', ['subscription_plan_id' => $plan->id])
        ->assertStatus(422);
});

// ---------------------------------------------------------------------------
// GET /api/v1/groups/{id}/owner-panel
// ---------------------------------------------------------------------------

test('owner can view owner panel', function () {
    $owner = makeTestUser();
    $plan  = makeTestGroupPlan();
    $group = makeTestGroup($owner, $plan);

    $this->actingAs($owner)
        ->getJson("/api/v1/groups/{$group->id}/owner-panel")
        ->assertOk()
        ->assertJsonStructure(['data' => ['group', 'memo_count', 'members', 'invites']]);
});

test('non-owner cannot view owner panel', function () {
    $owner = makeTestUser();
    $other = makeTestUser();
    $plan  = makeTestGroupPlan();
    $group = makeTestGroup($owner, $plan);

    $this->actingAs($other)
        ->getJson("/api/v1/groups/{$group->id}/owner-panel")
        ->assertStatus(403);
});

test('owner panel returns 404 for missing group', function () {
    $user = makeTestUser();
    $this->actingAs($user)->getJson('/api/v1/groups/99999/owner-panel')->assertStatus(404);
});

// ---------------------------------------------------------------------------
// POST /api/v1/groups/{id}/invites
// ---------------------------------------------------------------------------

test('owner can create invite', function () {
    $owner = makeTestUser();
    $plan  = makeTestGroupPlan();
    $group = makeTestGroup($owner, $plan);

    $res = $this->actingAs($owner)
        ->postJson("/api/v1/groups/{$group->id}/invites", ['email' => 'novo@example.com', 'role' => 'viewer'])
        ->assertCreated()
        ->assertJsonPath('data.email', 'novo@example.com')
        ->assertJsonPath('data.role', 'viewer')
        ->assertJsonPath('data.status', 'pending');

    expect(GroupInvite::where('email', 'novo@example.com')->exists())->toBeTrue();
});

test('non-owner cannot create invite', function () {
    $owner = makeTestUser();
    $other = makeTestUser();
    $plan  = makeTestGroupPlan();
    $group = makeTestGroup($owner, $plan);

    $this->actingAs($other)
        ->postJson("/api/v1/groups/{$group->id}/invites", ['email' => 'x@x.com', 'role' => 'viewer'])
        ->assertStatus(403);
});

test('duplicate pending invite is rejected', function () {
    $owner = makeTestUser();
    $plan  = makeTestGroupPlan();
    $group = makeTestGroup($owner, $plan);

    $this->actingAs($owner)
        ->postJson("/api/v1/groups/{$group->id}/invites", ['email' => 'dup@example.com', 'role' => 'editor'])
        ->assertCreated();

    $this->actingAs($owner)
        ->postJson("/api/v1/groups/{$group->id}/invites", ['email' => 'dup@example.com', 'role' => 'editor'])
        ->assertStatus(422);
});

// ---------------------------------------------------------------------------
// POST /api/v1/group-invites/accept
// ---------------------------------------------------------------------------

test('user can accept a valid invite', function () {
    $owner   = makeTestUser();
    $invitee = makeTestUser();
    $plan    = makeTestGroupPlan();
    $group   = makeTestGroup($owner, $plan);
    $token   = Str::random(64);

    GroupInvite::create([
        'group_id'           => $group->id,
        'email'              => $invitee->email,
        'role'               => 'viewer',
        'token'              => $token,
        'invited_by_user_id' => $owner->id,
        'expires_at'         => now()->addDays(7),
    ]);

    $this->actingAs($invitee)
        ->postJson('/api/v1/group-invites/accept', ['token' => $token])
        ->assertOk();

    expect(GroupMember::where('group_id', $group->id)->where('user_id', $invitee->id)->exists())->toBeTrue();
});

test('cannot accept invite with wrong email', function () {
    $owner    = makeTestUser();
    $wrongUser = makeTestUser();
    $plan     = makeTestGroupPlan();
    $group    = makeTestGroup($owner, $plan);
    $token    = Str::random(64);

    GroupInvite::create([
        'group_id'           => $group->id,
        'email'              => 'other@example.com',
        'role'               => 'viewer',
        'token'              => $token,
        'invited_by_user_id' => $owner->id,
        'expires_at'         => now()->addDays(7),
    ]);

    $this->actingAs($wrongUser)
        ->postJson('/api/v1/group-invites/accept', ['token' => $token])
        ->assertStatus(422);
});

test('cannot accept expired invite', function () {
    $owner   = makeTestUser();
    $invitee = makeTestUser();
    $plan    = makeTestGroupPlan();
    $group   = makeTestGroup($owner, $plan);
    $token   = Str::random(64);

    GroupInvite::create([
        'group_id'           => $group->id,
        'email'              => $invitee->email,
        'role'               => 'viewer',
        'token'              => $token,
        'invited_by_user_id' => $owner->id,
        'expires_at'         => now()->subDays(1),
    ]);

    $this->actingAs($invitee)
        ->postJson('/api/v1/group-invites/accept', ['token' => $token])
        ->assertStatus(422);
});

test('cannot accept nonexistent token', function () {
    $user  = makeTestUser();
    $token = Str::random(64);

    $this->actingAs($user)
        ->postJson('/api/v1/group-invites/accept', ['token' => $token])
        ->assertStatus(404);
});

test('guest cannot accept invite', function () {
    $this->postJson('/api/v1/group-invites/accept', ['token' => Str::random(64)])
        ->assertStatus(401);
});
