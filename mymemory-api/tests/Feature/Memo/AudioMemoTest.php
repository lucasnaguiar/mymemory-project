<?php

use App\Contracts\AiProviderInterface;
use App\DTOs\Memo\AiOutputDTO;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function makeAudioUser(array $planAttrs = []): User
{
    $plan = SubscriptionPlan::create(array_merge([
        'name'                  => 'Audio Plan',
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

function mockAudioAi(): void
{
    app()->bind(AiProviderInterface::class, fn () => new class implements AiProviderInterface {
        public function summarizeText(string $c, string $l): AiOutputDTO { return AiOutputDTO::empty(); }
        public function extractAndSummarizeUrl(string $u, string $l): AiOutputDTO { return AiOutputDTO::empty(); }
        public function analyzeImage(string $p, string $l): AiOutputDTO { return AiOutputDTO::empty(); }
        public function transcribeAudio(string $p, string $l): AiOutputDTO {
            return new AiOutputDTO('Audio summary', ['audio', 'transcript'], 'Full transcription text', 0.0);
        }
        public function transcribeVideo(string $p, string $l): AiOutputDTO { return AiOutputDTO::empty(); }
        public function extractDocument(string $p, string $m, string $l): AiOutputDTO { return AiOutputDTO::empty(); }
    });
}

// ---------------------------------------------------------------------------

test('guest cannot process audio', function () {
    $this->postJson('/api/v1/memos/audio/process', [])->assertStatus(401);
});

test('process audio returns draft with transcription', function () {
    Storage::fake('local');
    mockAudioAi();
    $user = makeAudioUser();

    $file = UploadedFile::fake()->create('podcast.mp3', 100, 'audio/mpeg');

    $this->actingAs($user)
        ->postJson('/api/v1/memos/audio/process', ['file' => $file, 'ai_level' => 'full'])
        ->assertOk()
        ->assertJsonPath('data.type', 'audio')
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.content', 'Full transcription text')
        ->assertJsonPath('data.summary', 'Audio summary');
});

test('process audio validates file type', function () {
    $user = makeAudioUser();
    $file = UploadedFile::fake()->image('not-audio.jpg');

    $this->actingAs($user)
        ->postJson('/api/v1/memos/audio/process', ['file' => $file])
        ->assertStatus(422);
});

test('plan without audio support is rejected', function () {
    $user = makeAudioUser(['supports_audio_video' => false]);
    $file = UploadedFile::fake()->create('x.mp3', 10, 'audio/mpeg');

    $this->actingAs($user)
        ->postJson('/api/v1/memos/audio/process', ['file' => $file])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'audio_video_not_supported');
});

test('confirm audio upgrades draft', function () {
    Storage::fake('local');
    mockAudioAi();
    $user = makeAudioUser();

    $res    = $this->actingAs($user)->postJson('/api/v1/memos/audio/process', [
        'file' => UploadedFile::fake()->create('a.mp3', 50, 'audio/mpeg'),
    ]);
    $memoId = $res->json('data.id');

    $this->actingAs($user)
        ->postJson('/api/v1/memos/audio/confirm', [
            'memo_id' => $memoId,
            'title'   => 'Podcast Episode',
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'confirmed');
});
