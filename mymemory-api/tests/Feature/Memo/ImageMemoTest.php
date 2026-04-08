<?php

use App\Contracts\AiProviderInterface;
use App\DTOs\Memo\AiOutputDTO;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function makeImageUser(array $planAttrs = []): User
{
    $plan = SubscriptionPlan::create(array_merge([
        'name'                  => 'Image Plan',
        'type'                  => 'individual',
        'is_active'             => true,
        'storage_gb'            => 5,
        'max_memos'             => 50,
        'api_credits_per_month' => null,
        'downloads_per_month'   => null,
        'price_cents'           => 0,
        'supports_audio_video'  => true,
    ], $planAttrs));

    return User::factory()->create(['subscription_plan_id' => $plan->id]);
}

function mockImageAi(): void
{
    app()->bind(AiProviderInterface::class, fn () => new class implements AiProviderInterface {
        public function summarizeText(string $c, string $l): AiOutputDTO { return AiOutputDTO::empty(); }
        public function extractAndSummarizeUrl(string $u, string $l): AiOutputDTO { return AiOutputDTO::empty(); }
        public function analyzeImage(string $p, string $l): AiOutputDTO {
            return new AiOutputDTO('Mock image description', ['mock', 'image'], 'OCR text', 0.0);
        }
        public function transcribeAudio(string $p, string $l): AiOutputDTO { return AiOutputDTO::empty(); }
        public function transcribeVideo(string $p, string $l): AiOutputDTO { return AiOutputDTO::empty(); }
        public function extractDocument(string $p, string $m, string $l): AiOutputDTO { return AiOutputDTO::empty(); }
    });
}

// ---------------------------------------------------------------------------

test('guest cannot process image', function () {
    $file = UploadedFile::fake()->image('test.jpg');
    $this->postJson('/api/v1/memos/image/process', ['file' => $file])->assertStatus(401);
});

test('process image returns draft with ai suggestion', function () {
    Storage::fake('local');
    mockImageAi();
    $user = makeImageUser();

    $file = UploadedFile::fake()->image('photo.jpg');

    $this->actingAs($user)
        ->postJson('/api/v1/memos/image/process', ['file' => $file, 'ai_level' => 'full'])
        ->assertOk()
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.type', 'image')
        ->assertJsonPath('data.summary', 'Mock image description')
        ->assertJsonStructure(['data' => ['id', 'status', 'type', 'summary', 'file']]);
});

test('process image validates file type', function () {
    $user = makeImageUser();

    $this->actingAs($user)
        ->postJson('/api/v1/memos/image/process', [
            'file'     => UploadedFile::fake()->create('bad.mp3', 10, 'audio/mpeg'),
            'ai_level' => 'full',
        ])
        ->assertStatus(422);
});

test('confirm image upgrades draft to confirmed', function () {
    Storage::fake('local');
    mockImageAi();
    $user = makeImageUser();

    $res    = $this->actingAs($user)->postJson('/api/v1/memos/image/process', [
        'file'     => UploadedFile::fake()->image('a.jpg'),
        'ai_level' => 'full',
    ]);
    $memoId = $res->json('data.id');

    $this->actingAs($user)
        ->postJson('/api/v1/memos/image/confirm', [
            'memo_id' => $memoId,
            'title'   => 'My Photo',
            'summary' => 'Custom summary',
            'keywords' => ['photo'],
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonPath('data.title', 'My Photo');
});

test('cannot confirm image draft of another user', function () {
    Storage::fake('local');
    mockImageAi();
    $owner = makeImageUser();
    $other = makeImageUser();

    $res    = $this->actingAs($owner)->postJson('/api/v1/memos/image/process', [
        'file' => UploadedFile::fake()->image('x.jpg'),
    ]);
    $memoId = $res->json('data.id');

    $this->actingAs($other)
        ->postJson('/api/v1/memos/image/confirm', ['memo_id' => $memoId])
        ->assertStatus(403);
});

test('confirm image blocked when plan limit reached', function () {
    Storage::fake('local');
    mockImageAi();
    $user = makeImageUser(['max_memos' => 0]);

    $res    = $this->actingAs($user)->postJson('/api/v1/memos/image/process', [
        'file' => UploadedFile::fake()->image('x.jpg'),
    ]);
    $memoId = $res->json('data.id');

    $this->actingAs($user)
        ->postJson('/api/v1/memos/image/confirm', ['memo_id' => $memoId])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'memo_limit_reached');
});
