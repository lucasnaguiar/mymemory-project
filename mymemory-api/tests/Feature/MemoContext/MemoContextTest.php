<?php

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\MemoContextCategory;
use App\Models\MemoContextField;
use App\Models\MemoContextSubcategory;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function ctxPlan(): SubscriptionPlan
{
    return SubscriptionPlan::create([
        'name' => 'Plan', 'type' => 'individual', 'is_active' => true,
        'storage_gb' => 5, 'price_cents' => 0,
    ]);
}

function ctxUser(string $role = 'user'): User
{
    return User::factory()->create(['subscription_plan_id' => ctxPlan()->id, 'role' => $role]);
}

function ctxAdmin(): User  { return ctxUser('admin'); }

function ctxGroup(User $owner): Group
{
    $plan = SubscriptionPlan::create([
        'name' => 'Group Plan', 'type' => 'group', 'is_active' => true,
        'storage_gb' => 20, 'price_cents' => 0,
    ]);
    return Group::create(['name' => 'Test Group', 'owner_user_id' => $owner->id, 'subscription_plan_id' => $plan->id]);
}

function ctxCategory(User $creator, string $scope = 'global', ?Group $group = null): MemoContextCategory
{
    return MemoContextCategory::create([
        'name'               => 'Cat ' . uniqid(),
        'scope'              => $scope,
        'group_id'           => $group?->id,
        'created_by_user_id' => $creator->id,
    ]);
}

// ---------------------------------------------------------------------------
// GET /memo-context/structure
// ---------------------------------------------------------------------------

test('guest cannot access structure', function () {
    $this->getJson('/api/v1/memo-context/structure')->assertStatus(401);
});

test('user can retrieve structure', function () {
    $user  = ctxUser();
    $admin = ctxAdmin();
    ctxCategory($admin, 'global');

    $res = $this->actingAs($user)
        ->getJson('/api/v1/memo-context/structure')
        ->assertOk()
        ->assertJsonStructure(['data']);

    expect(count($res->json('data')))->toBeGreaterThanOrEqual(1);
});

test('structure without groupId returns only global categories', function () {
    $admin = ctxAdmin();
    $owner = ctxUser();
    $group = ctxGroup($owner);

    ctxCategory($admin, 'global');
    ctxCategory($owner, 'group', $group);

    $res = $this->actingAs($owner)
        ->getJson('/api/v1/memo-context/structure')
        ->assertOk();

    $scopes = array_column($res->json('data'), 'scope');
    expect($scopes)->not->toContain('group');
    expect($scopes)->toContain('global');
});

test('structure with groupId returns global + group categories', function () {
    $admin = ctxAdmin();
    $owner = ctxUser();
    $group = ctxGroup($owner);

    ctxCategory($admin, 'global');
    ctxCategory($owner, 'group', $group);

    $res = $this->actingAs($owner)
        ->getJson("/api/v1/memo-context/structure?groupId={$group->id}")
        ->assertOk();

    $scopes = array_column($res->json('data'), 'scope');
    expect($scopes)->toContain('global');
    expect($scopes)->toContain('group');
});

test('mediaType filter excludes non-matching categories', function () {
    $admin = ctxAdmin();
    ctxCategory($admin, 'global'); // no filter = always shown
    MemoContextCategory::create([
        'name' => 'Image Cat', 'scope' => 'global', 'group_id' => null,
        'created_by_user_id' => $admin->id, 'media_type_filter' => 'image',
    ]);
    MemoContextCategory::create([
        'name' => 'Audio Cat', 'scope' => 'global', 'group_id' => null,
        'created_by_user_id' => $admin->id, 'media_type_filter' => 'audio',
    ]);

    $user = ctxUser();
    $res  = $this->actingAs($user)
        ->getJson('/api/v1/memo-context/structure?mediaType=image')
        ->assertOk();

    $names = array_column($res->json('data'), 'name');
    expect($names)->not->toContain('Audio Cat');
    expect($names)->toContain('Image Cat');
});

// ---------------------------------------------------------------------------
// GET /memo-context/editor-meta
// ---------------------------------------------------------------------------

test('editor-meta for regular user returns canEditGlobal=false', function () {
    $user = ctxUser();
    $res  = $this->actingAs($user)->getJson('/api/v1/memo-context/editor-meta')->assertOk();
    expect($res->json('data.can_edit_global'))->toBeFalse();
    expect($res->json('data.editable_group_ids'))->toBeArray();
});

test('editor-meta for admin returns canEditGlobal=true', function () {
    $admin = ctxAdmin();
    $res   = $this->actingAs($admin)->getJson('/api/v1/memo-context/editor-meta')->assertOk();
    expect($res->json('data.can_edit_global'))->toBeTrue();
});

test('editor-meta includes groups where user is owner', function () {
    $owner = ctxUser();
    $group = ctxGroup($owner);
    $res   = $this->actingAs($owner)->getJson('/api/v1/memo-context/editor-meta')->assertOk();
    expect($res->json('data.editable_group_ids'))->toContain($group->id);
});

// ---------------------------------------------------------------------------
// Category CRUD
// ---------------------------------------------------------------------------

test('admin can create global category', function () {
    $admin = ctxAdmin();
    $res   = $this->actingAs($admin)
        ->postJson('/api/v1/memo-context/categories', [
            'name' => 'Science', 'scope' => 'global',
        ])
        ->assertCreated()
        ->assertJsonPath('data.scope', 'global');

    expect(MemoContextCategory::where('name', 'Science')->exists())->toBeTrue();
});

test('regular user cannot create global category', function () {
    $user = ctxUser();
    $this->actingAs($user)
        ->postJson('/api/v1/memo-context/categories', ['name' => 'Hack', 'scope' => 'global'])
        ->assertStatus(403);
});

test('group owner can create group category', function () {
    $owner = ctxUser();
    $group = ctxGroup($owner);

    $res = $this->actingAs($owner)
        ->postJson('/api/v1/memo-context/categories', [
            'name' => 'Team Cat', 'scope' => 'group', 'group_id' => $group->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.scope', 'group')
        ->assertJsonPath('data.group_id', $group->id);

    expect(MemoContextCategory::where('name', 'Team Cat')->exists())->toBeTrue();
});

test('non-owner cannot create group category for another group', function () {
    $owner = ctxUser();
    $other = ctxUser();
    $group = ctxGroup($owner);

    $this->actingAs($other)
        ->postJson('/api/v1/memo-context/categories', [
            'name' => 'Hack', 'scope' => 'group', 'group_id' => $group->id,
        ])
        ->assertStatus(403);
});

test('editor member can create group category', function () {
    $owner  = ctxUser();
    $editor = ctxUser();
    $group  = ctxGroup($owner);
    GroupMember::create(['group_id' => $group->id, 'user_id' => $editor->id, 'role' => 'editor', 'joined_at' => now()]);

    $this->actingAs($editor)
        ->postJson('/api/v1/memo-context/categories', [
            'name' => 'Editor Cat', 'scope' => 'group', 'group_id' => $group->id,
        ])
        ->assertCreated();
});

test('viewer member cannot create group category', function () {
    $owner  = ctxUser();
    $viewer = ctxUser();
    $group  = ctxGroup($owner);
    GroupMember::create(['group_id' => $group->id, 'user_id' => $viewer->id, 'role' => 'viewer', 'joined_at' => now()]);

    $this->actingAs($viewer)
        ->postJson('/api/v1/memo-context/categories', [
            'name' => 'Viewer Cat', 'scope' => 'group', 'group_id' => $group->id,
        ])
        ->assertStatus(403);
});

test('admin can update and delete global category', function () {
    $admin = ctxAdmin();
    $cat   = ctxCategory($admin, 'global');

    $this->actingAs($admin)
        ->patchJson("/api/v1/memo-context/categories/{$cat->id}", ['name' => 'Updated'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated');

    $this->actingAs($admin)
        ->deleteJson("/api/v1/memo-context/categories/{$cat->id}")
        ->assertNoContent();

    expect(MemoContextCategory::find($cat->id))->toBeNull();
    expect(MemoContextCategory::withTrashed()->find($cat->id))->not->toBeNull();
});

// ---------------------------------------------------------------------------
// Subcategory CRUD
// ---------------------------------------------------------------------------

test('admin can add and delete subcategory', function () {
    $admin = ctxAdmin();
    $cat   = ctxCategory($admin, 'global');

    $res = $this->actingAs($admin)
        ->postJson("/api/v1/memo-context/categories/{$cat->id}/subcategories", ['name' => 'Sub1'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Sub1');

    $subId = $res->json('data.id');

    $this->actingAs($admin)
        ->deleteJson("/api/v1/memo-context/subcategories/{$subId}")
        ->assertNoContent();

    expect(MemoContextSubcategory::find($subId))->toBeNull();
});

// ---------------------------------------------------------------------------
// Field CRUD
// ---------------------------------------------------------------------------

test('admin can add and delete field', function () {
    $admin = ctxAdmin();
    $cat   = ctxCategory($admin, 'global');

    $res = $this->actingAs($admin)
        ->postJson("/api/v1/memo-context/categories/{$cat->id}/fields", [
            'name' => 'Source', 'field_type' => 'text', 'is_required' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Source')
        ->assertJsonPath('data.field_type', 'text')
        ->assertJsonPath('data.is_required', true);

    $fieldId = $res->json('data.id');

    $this->actingAs($admin)
        ->deleteJson("/api/v1/memo-context/fields/{$fieldId}")
        ->assertNoContent();

    expect(MemoContextField::find($fieldId))->toBeNull();
});

test('regular user cannot delete global category field', function () {
    $admin = ctxAdmin();
    $user  = ctxUser();
    $cat   = ctxCategory($admin, 'global');
    $field = MemoContextField::create([
        'category_id' => $cat->id, 'name' => 'Field', 'field_type' => 'text', 'is_required' => false,
    ]);

    $this->actingAs($user)
        ->deleteJson("/api/v1/memo-context/fields/{$field->id}")
        ->assertStatus(403);
});
