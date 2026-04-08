<?php

use App\Contracts\AiProviderInterface;
use App\DTOs\Memo\AiOutputDTO;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function makeVideoUser(array $planAttrs = []): User
{
    $plan = SubscriptionPlan::create(array_merge([
        'name'                  => 'Video Plan',
        'type'                  => 'individual',
        'is_active'             => true,
        'storage_gb'            => 20,
        'max_memos'             => 50,
        'api_credits_per_month' => null,
        'downloads_per_month'   => null,
        'price_cents'           => 0,
        'supports_audio_video'  => true,
    ], $planAttrs));

    return User::factory()->create(['subscription_plan_id' => $plan->id]);
}

function mockVideoAi(): void
{
    app()->bind(AiProviderInterface::class, fn () => new class implements AiProviderInterface {
        public function summarizeText(string $c, string $l): AiOutputDTO { return AiOutputDTO::empty(); }
        public function extractAndSummarizeUrl(string $u, string $l): AiOutputDTO { return AiOutputDTO::empty(); }
        public function analyzeImage(string $p, string $l): AiOutputDTO { return AiOutputDTO::empty(); }
        public function transcribeAudio(string $p, string $l): AiOutputDTO { return AiOutputDTO::empty(); }
        public function transcribeVideo(string $p, string $l): AiOutputDTO {
            return new AiOutputDTO('Video summary', ['video', 'mock'], 'Video transcript', 0.0);
        }
        public function extractDocument(string $p, string $m, string $l): AiOutputDTO { return AiOutputDTO::empty(); }
    });
}

// ---------------------------------------------------------------------------

test('guest cannot process video', function () {
    $this->postJson('/api/v1/memos/video/process', [])->assertStatus(401);
});

test('process video returns draft with transcript', function () {
    Storage::fake('local');
    mockVideoAi();
    $user = makeVideoUser();

    $file = UploadedFile::fake()->create('clip.mp4', 500, 'video/mp4');

    $this->actingAs($user)
        ->postJson('/api/v1/memos/video/process', ['file' => $file, 'ai_level' => 'full'])
        ->assertOk()
        ->assertJsonPath('data.type', 'video')
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.content', 'Video transcript');
});

test('plan without video support is rejected', function () {
    $user = makeVideoUser(['supports_audio_video' => false]);
    $file = UploadedFile::fake()->create('x.mp4', 10, 'video/mp4');

    $this->actingAs($user)
        ->postJson('/api/v1/memos/video/process', ['file' => $file])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'audio_video_not_supported');
});

test('confirm video upgrades draft', function () {
    Storage::fake('local');
    mockVideoAi();
    $user = makeVideoUser();

    $res    = $this->actingAs($user)->postJson('/api/v1/memos/video/process', [
        'file' => UploadedFile::fake()->create('v.mp4', 100, 'video/mp4'),
    ]);
    $memoId = $res->json('data.id');

    $this->actingAs($user)
        ->postJson('/api/v1/memos/video/confirm', [
            'memo_id' => $memoId,
            'summary' => 'Final summary',
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'confirmed');
});
