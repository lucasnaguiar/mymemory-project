<?php

use App\Models\Memo;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function adminUser(): User
{
    $plan = SubscriptionPlan::create([
        'name' => 'Admin Plan', 'type' => 'individual', 'is_active' => true,
        'storage_gb' => 10, 'price_cents' => 0,
    ]);
    return User::factory()->create(['subscription_plan_id' => $plan->id, 'role' => 'admin']);
}

function regularUser(): User
{
    $plan = SubscriptionPlan::first() ?? SubscriptionPlan::create([
        'name' => 'Regular Plan', 'type' => 'individual', 'is_active' => true,
        'storage_gb' => 5, 'price_cents' => 0,
    ]);
    return User::factory()->create(['subscription_plan_id' => $plan->id, 'role' => 'user']);
}

// ---------------------------------------------------------------------------
// Middleware guard
// ---------------------------------------------------------------------------

test('guest cannot access admin routes', function () {
    $this->getJson('/api/v1/admin/subscription-plans')->assertStatus(401);
});

test('regular user cannot access admin routes', function () {
    $user = regularUser();
    $this->actingAs($user)->getJson('/api/v1/admin/subscription-plans')->assertStatus(403);
});

// ---------------------------------------------------------------------------
// Subscription plans CRUD
// ---------------------------------------------------------------------------

test('admin can list subscription plans', function () {
    $admin = adminUser();
    $this->actingAs($admin)->getJson('/api/v1/admin/subscription-plans')
        ->assertOk()
        ->assertJsonStructure(['data']);
});

test('admin can create subscription plan', function () {
    $admin = adminUser();
    $res   = $this->actingAs($admin)
        ->postJson('/api/v1/admin/subscription-plans', [
            'name'       => 'Pro Plan',
            'type'       => 'individual',
            'storage_gb' => 50,
            'is_active'  => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Pro Plan');

    expect(SubscriptionPlan::where('name', 'Pro Plan')->exists())->toBeTrue();
});

test('admin can update plan', function () {
    $admin = adminUser();
    $plan  = SubscriptionPlan::create([
        'name' => 'Old Plan', 'type' => 'individual', 'is_active' => true,
        'storage_gb' => 5, 'price_cents' => 0,
    ]);

    $this->actingAs($admin)
        ->patchJson("/api/v1/admin/subscription-plans/{$plan->id}", [
            'name' => 'Updated Plan', 'type' => 'individual', 'storage_gb' => 10,
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Plan');
});

test('admin can delete plan without subscribers', function () {
    $admin = adminUser();
    $plan  = SubscriptionPlan::create([
        'name' => 'Unused Plan', 'type' => 'group', 'is_active' => false,
        'storage_gb' => 1, 'price_cents' => 0,
    ]);

    $this->actingAs($admin)
        ->deleteJson("/api/v1/admin/subscription-plans/{$plan->id}")
        ->assertNoContent();

    expect(SubscriptionPlan::find($plan->id))->toBeNull();
});

test('admin cannot delete plan with active subscribers', function () {
    $admin   = adminUser();
    $userPlan = SubscriptionPlan::first();

    $this->actingAs($admin)
        ->deleteJson("/api/v1/admin/subscription-plans/{$userPlan->id}")
        ->assertStatus(422);
});

// ---------------------------------------------------------------------------
// Media settings
// ---------------------------------------------------------------------------

test('admin can upsert media settings for a plan', function () {
    $admin = adminUser();
    $plan  = SubscriptionPlan::first();

    $this->actingAs($admin)
        ->putJson("/api/v1/admin/subscription-plans/{$plan->id}/media-settings", [
            'media_type'       => 'image',
            'max_file_size_mb' => 200,
        ])
        ->assertOk()
        ->assertJsonPath('data.media_type', 'image')
        ->assertJsonPath('data.max_file_size_mb', 200);
});

test('admin can get media settings for a plan', function () {
    $admin = adminUser();
    $plan  = SubscriptionPlan::first();

    $this->actingAs($admin)
        ->getJson("/api/v1/admin/subscription-plans/{$plan->id}/media-settings")
        ->assertOk()
        ->assertJsonStructure(['data']);
});

// ---------------------------------------------------------------------------
// Document AI routing
// ---------------------------------------------------------------------------

test('admin can get document ai routing', function () {
    $admin = adminUser();
    $this->actingAs($admin)
        ->getJson('/api/v1/admin/document-ai-routing')
        ->assertOk()
        ->assertJsonStructure(['data' => ['config', 'using_defaults']]);
});

test('admin can update document ai routing', function () {
    $admin  = adminUser();
    $config = ['pdf' => 'vision', 'docx' => 'extract_text'];

    $this->actingAs($admin)
        ->putJson('/api/v1/admin/document-ai-routing', ['config' => $config])
        ->assertOk()
        ->assertJsonPath('data.using_defaults', false);
});

// ---------------------------------------------------------------------------
// Cost report
// ---------------------------------------------------------------------------

test('admin can access cost report', function () {
    $admin = adminUser();
    $this->actingAs($admin)
        ->getJson('/api/v1/admin/cost-report')
        ->assertOk()
        ->assertJsonStructure(['data' => ['rows', 'total_memos', 'period']]);
});

// ---------------------------------------------------------------------------
// Soft-delete management
// ---------------------------------------------------------------------------

test('admin can view soft-deleted summary', function () {
    $admin = adminUser();
    $this->actingAs($admin)
        ->getJson('/api/v1/admin/soft-deleted-memos/monthly-summary')
        ->assertOk()
        ->assertJsonStructure(['data']);
});

test('admin can hard-delete a month of soft-deleted memos', function () {
    $admin = adminUser();
    $user  = regularUser();

    // Create a soft-deleted memo
    $memo = Memo::create([
        'user_id'  => $user->id,
        'type'     => 'text',
        'status'   => 'confirmed',
        'content'  => 'Old memo',
        'ai_level' => 'none',
    ]);
    $memo->delete();

    $this->actingAs($admin)
        ->deleteJson('/api/v1/admin/soft-deleted-memos/hard-delete-month', [
            'year'  => now()->year,
            'month' => now()->month,
        ])
        ->assertOk()
        ->assertJsonPath('data.deleted_count', 1);

    expect(Memo::withTrashed()->find($memo->id))->toBeNull();
});
