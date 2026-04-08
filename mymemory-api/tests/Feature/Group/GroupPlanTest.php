<?php

use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeGroupPlan(array $attrs = []): SubscriptionPlan
{
    return SubscriptionPlan::create(array_merge([
        'name'       => 'Group Plan',
        'type'       => 'group',
        'is_active'  => true,
        'storage_gb' => 20,
        'max_memos'  => 200,
        'price_cents' => 1990,
    ], $attrs));
}

test('guest can list active group plans', function () {
    makeGroupPlan(['name' => 'Starter Group']);
    makeGroupPlan(['name' => 'Pro Group', 'price_cents' => 4990]);
    makeGroupPlan(['name' => 'Inactive', 'is_active' => false]);

    $res = $this->getJson('/api/v1/group-plans')->assertOk();

    $names = array_column($res->json('data'), 'name');
    expect($names)->toContain('Starter Group');
    expect($names)->toContain('Pro Group');
    expect($names)->not->toContain('Inactive');
});

test('individual plans are not listed', function () {
    SubscriptionPlan::create([
        'name' => 'Individual Plan', 'type' => 'individual', 'is_active' => true,
        'storage_gb' => 5, 'max_memos' => 50, 'price_cents' => 0,
    ]);
    makeGroupPlan(['name' => 'Group Plan']);

    $res = $this->getJson('/api/v1/group-plans')->assertOk();
    $names = array_column($res->json('data'), 'name');
    expect($names)->not->toContain('Individual Plan');
    expect($names)->toContain('Group Plan');
});

test('response includes expected fields', function () {
    makeGroupPlan();
    $this->getJson('/api/v1/group-plans')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'name', 'price_cents', 'storage_gb', 'max_memos']]]);
});
