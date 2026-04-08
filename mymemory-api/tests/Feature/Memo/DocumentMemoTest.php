<?php

use App\Contracts\AiProviderInterface;
use App\DTOs\Memo\AiOutputDTO;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function makeDocumentUser(array $planAttrs = []): User
{
    $plan = SubscriptionPlan::create(array_merge([
        'name'                  => 'Doc Plan',
        'type'                  => 'individual',
        'is_active'             => true,
        'storage_gb'            => 5,
        'max_memos'             => 50,
        'api_credits_per_month' => null,
        'downloads_per_month'   => null,
        'price_cents'           => 0,
    ], $planAttrs));

    return User::factory()->create(['subscription_plan_id' => $plan->id]);
}

function mockDocumentAi(): void
{
    app()->bind(AiProviderInterface::class, fn () => new class implements AiProviderInterface {
        public function summarizeText(string $c, string $l): AiOutputDTO { return AiOutputDTO::empty(); }
        public function extractAndSummarizeUrl(string $u, string $l): AiOutputDTO { return AiOutputDTO::empty(); }
        public function analyzeImage(string $p, string $l): AiOutputDTO { return AiOutputDTO::empty(); }
        public function transcribeAudio(string $p, string $l): AiOutputDTO { return AiOutputDTO::empty(); }
        public function transcribeVideo(string $p, string $l): AiOutputDTO { return AiOutputDTO::empty(); }
        public function extractDocument(string $p, string $m, string $l): AiOutputDTO {
            return new AiOutputDTO('Document summary', ['doc', 'mock'], 'Extracted document text', 0.0);
        }
    });
}

// ---------------------------------------------------------------------------

test('guest cannot process document', function () {
    $this->postJson('/api/v1/memos/document/process', [])->assertStatus(401);
});

test('process document returns draft with extracted content', function () {
    Storage::fake('local');
    mockDocumentAi();
    $user = makeDocumentUser();

    $file = UploadedFile::fake()->create('report.pdf', 200, 'application/pdf');

    $this->actingAs($user)
        ->postJson('/api/v1/memos/document/process', ['file' => $file, 'ai_level' => 'full'])
        ->assertOk()
        ->assertJsonPath('data.type', 'document')
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.content', 'Extracted document text')
        ->assertJsonPath('data.summary', 'Document summary');
});

test('process document validates allowed mime types', function () {
    $user = makeDocumentUser();
    $file = UploadedFile::fake()->image('photo.jpg');

    $this->actingAs($user)
        ->postJson('/api/v1/memos/document/process', ['file' => $file])
        ->assertStatus(422);
});

test('confirm document upgrades draft', function () {
    Storage::fake('local');
    mockDocumentAi();
    $user = makeDocumentUser();

    $res    = $this->actingAs($user)->postJson('/api/v1/memos/document/process', [
        'file' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
    ]);
    $memoId = $res->json('data.id');

    $this->actingAs($user)
        ->postJson('/api/v1/memos/document/confirm', [
            'memo_id'  => $memoId,
            'title'    => 'Q4 Report',
            'keywords' => ['finance', 'report'],
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonPath('data.title', 'Q4 Report');
});

test('cannot confirm document of another user', function () {
    Storage::fake('local');
    mockDocumentAi();
    $owner = makeDocumentUser();
    $other = makeDocumentUser();

    $res    = $this->actingAs($owner)->postJson('/api/v1/memos/document/process', [
        'file' => UploadedFile::fake()->create('d.pdf', 50, 'application/pdf'),
    ]);
    $memoId = $res->json('data.id');

    $this->actingAs($other)
        ->postJson('/api/v1/memos/document/confirm', ['memo_id' => $memoId])
        ->assertStatus(403);
});
