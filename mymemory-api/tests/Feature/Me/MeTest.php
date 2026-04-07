<?php

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makePlan(array $attrs = []): SubscriptionPlan
{
    return SubscriptionPlan::create(array_merge([
        'name'                  => 'Test Plan',
        'type'                  => 'individual',
        'is_active'             => true,
        'storage_gb'            => 5,
        'max_memos'             => 100,
        'api_credits_per_month' => 500,
        'downloads_per_month'   => 50,
        'price_cents'           => 0,
    ], $attrs));
}

function makeUser(array $attrs = []): User
{
    $plan = makePlan();

    return User::factory()->create(array_merge([
        'subscription_plan_id' => $plan->id,
    ], $attrs));
}

// ---------------------------------------------------------------------------
// GET /api/v1/me
// ---------------------------------------------------------------------------

test('guest cannot access profile', function () {
    $this->getJson('/api/v1/me')->assertStatus(401);
});

test('authenticated user can get their profile', function () {
    $user = makeUser();

    $this->actingAs($user)
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonStructure([
            'data' => ['id', 'name', 'email', 'role', 'subscription_plan'],
        ]);
});

// ---------------------------------------------------------------------------
// GET /api/v1/me/usage
// ---------------------------------------------------------------------------

test('authenticated user can get usage', function () {
    $user = makeUser();

    $this->actingAs($user)
        ->getJson('/api/v1/me/usage')
        ->assertOk()
        ->assertJsonStructure([
            'data' => ['plan_name', 'memos', 'storage_bytes', 'api_credits', 'downloads'],
        ])
        ->assertJsonPath('data.memos.used', 0)
        ->assertJsonPath('data.memos.limit', 100);
});

// ---------------------------------------------------------------------------
// GET /api/v1/me/media-limits
// ---------------------------------------------------------------------------

test('authenticated user can get media limits', function () {
    $user = makeUser();

    $this->actingAs($user)
        ->getJson('/api/v1/me/media-limits')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'image'    => ['max_file_size_mb', 'max_chunk_minutes', 'ocr_correction_threshold_default'],
                'audio'    => ['max_file_size_mb'],
                'video'    => ['max_file_size_mb'],
                'document' => ['max_file_size_mb'],
            ],
        ]);
});

// ---------------------------------------------------------------------------
// PATCH /api/v1/me/preferences
// ---------------------------------------------------------------------------

test('user can update preferences', function () {
    $user = makeUser();

    $this->actingAs($user)
        ->patchJson('/api/v1/me/preferences', [
            'ai_level_text'             => 'basic',
            'sound_enabled'             => false,
            'confirm_before_processing' => true,
            'ocr_correction_threshold'  => 75,
        ])
        ->assertOk()
        ->assertJsonPath('data.ai_level_text', 'basic')
        ->assertJsonPath('data.sound_enabled', false)
        ->assertJsonPath('data.ocr_correction_threshold', 75);
});

test('user can clear ocr threshold by sending null', function () {
    $user = makeUser();
    UserPreference::create([
        'user_id'                  => $user->id,
        'ocr_correction_threshold' => 80,
    ]);

    $this->actingAs($user)
        ->patchJson('/api/v1/me/preferences', ['ocr_correction_threshold' => null])
        ->assertOk()
        ->assertJsonPath('data.ocr_correction_threshold', null);
});

test('preferences validation rejects invalid ai level', function () {
    $user = makeUser();

    $this->actingAs($user)
        ->patchJson('/api/v1/me/preferences', ['ai_level_text' => 'ultra'])
        ->assertStatus(422);
});

test('preferences validation rejects ocr threshold out of range', function () {
    $user = makeUser();

    $this->actingAs($user)
        ->patchJson('/api/v1/me/preferences', ['ocr_correction_threshold' => 150])
        ->assertStatus(422);
});

// ---------------------------------------------------------------------------
// GET /api/v1/me/workspace-groups
// ---------------------------------------------------------------------------

test('user sees groups they own and belong to', function () {
    $user  = makeUser();
    $plan  = makePlan(['type' => 'group']);
    $owned = Group::create(['name' => 'My Group', 'owner_user_id' => $user->id, 'subscription_plan_id' => $plan->id]);

    $other = makeUser();
    $g2    = Group::create(['name' => 'Other Group', 'owner_user_id' => $other->id, 'subscription_plan_id' => $plan->id]);
    GroupMember::create(['group_id' => $g2->id, 'user_id' => $user->id, 'role' => 'viewer']);

    $response = $this->actingAs($user)
        ->getJson('/api/v1/me/workspace-groups')
        ->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toContain($owned->id)->toContain($g2->id);
});

// ---------------------------------------------------------------------------
// PATCH /api/v1/me/workspace
// ---------------------------------------------------------------------------

test('user can switch to personal workspace by sending null', function () {
    $user = makeUser(['active_workspace_group_id' => null]);

    $this->actingAs($user)
        ->patchJson('/api/v1/me/workspace', ['group_id' => null])
        ->assertOk()
        ->assertJsonPath('data.active_workspace_group_id', null);
});

test('user can switch to a group they belong to', function () {
    $user  = makeUser();
    $plan  = makePlan(['type' => 'group']);
    $group = Group::create(['name' => 'G', 'owner_user_id' => $user->id, 'subscription_plan_id' => $plan->id]);

    $this->actingAs($user)
        ->patchJson('/api/v1/me/workspace', ['group_id' => $group->id])
        ->assertOk()
        ->assertJsonPath('data.active_workspace_group_id', $group->id);
});

test('user cannot switch to a group they do not belong to', function () {
    $user  = makeUser();
    $other = makeUser();
    $plan  = makePlan(['type' => 'group']);
    $group = Group::create(['name' => 'G', 'owner_user_id' => $other->id, 'subscription_plan_id' => $plan->id]);

    $this->actingAs($user)
        ->patchJson('/api/v1/me/workspace', ['group_id' => $group->id])
        ->assertStatus(403);
});

test('workspace update rejects non-existent group', function () {
    $user = makeUser();

    $this->actingAs($user)
        ->patchJson('/api/v1/me/workspace', ['group_id' => 99999])
        ->assertStatus(422);
});
