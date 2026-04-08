<?php

use App\Http\Controllers\Api\V1\Group\GroupController;
use App\Http\Controllers\Api\V1\Group\GroupPlanController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\Memo\AudioMemoController;
use App\Http\Controllers\Api\V1\Memo\MemoController;
use App\Http\Controllers\Api\V1\Memo\DocumentMemoController;
use App\Http\Controllers\Api\V1\Memo\ImageMemoController;
use App\Http\Controllers\Api\V1\Memo\TextMemoController;
use App\Http\Controllers\Api\V1\Memo\UrlMemoController;
use App\Http\Controllers\Api\V1\Memo\VideoMemoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — v1
|--------------------------------------------------------------------------
|
| All routes here are prefixed with /api/v1 (configured in bootstrap/app.php).
| Authentication uses Laravel Sanctum SPA cookies (stateful, httpOnly,
| cookie name: mm_access). See docs/auth-decision.md for the full rationale.
|
*/

Route::get('/health', HealthController::class)->name('health');

// Public — group plans listing
Route::get('/group-plans', [GroupPlanController::class, 'index'])->name('group-plans.index');

// ---------------------------------------------------------------------------
// Authenticated routes (Sanctum stateful guard)
// ---------------------------------------------------------------------------
Route::middleware('auth:sanctum')->group(function () {

    // Me — profile, usage, preferences, workspace
    Route::prefix('me')->name('me.')->group(function () {
        Route::get('/',                [MeController::class, 'profile'])->name('profile');
        Route::get('/usage',           [MeController::class, 'usage'])->name('usage');
        Route::get('/media-limits',    [MeController::class, 'mediaLimits'])->name('media-limits');
        Route::patch('/preferences',   [MeController::class, 'updatePreferences'])->name('preferences.update');
        Route::get('/workspace-groups',[MeController::class, 'workspaceGroups'])->name('workspace-groups');
        Route::patch('/workspace',     [MeController::class, 'updateWorkspace'])->name('workspace.update');
    });

    // Memos — text
    Route::prefix('memos/text')->name('memos.text.')->group(function () {
        Route::post('/process', [TextMemoController::class, 'process'])->name('process');
        Route::post('/confirm', [TextMemoController::class, 'confirm'])->name('confirm');
        Route::post('/',        [TextMemoController::class, 'create'])->name('create');
    });

    // Memos — URL
    Route::prefix('memos/url')->name('memos.url.')->group(function () {
        Route::post('/process', [UrlMemoController::class, 'process'])->name('process');
        Route::post('/confirm', [UrlMemoController::class, 'confirm'])->name('confirm');
        Route::post('/',        [UrlMemoController::class, 'create'])->name('create');
    });

    // Memos — image
    Route::prefix('memos/image')->name('memos.image.')->group(function () {
        Route::post('/process', [ImageMemoController::class, 'process'])->name('process');
        Route::post('/confirm', [ImageMemoController::class, 'confirm'])->name('confirm');
    });

    // Memos — audio
    Route::prefix('memos/audio')->name('memos.audio.')->group(function () {
        Route::post('/process', [AudioMemoController::class, 'process'])->name('process');
        Route::post('/confirm', [AudioMemoController::class, 'confirm'])->name('confirm');
    });

    // Memos — video
    Route::prefix('memos/video')->name('memos.video.')->group(function () {
        Route::post('/process', [VideoMemoController::class, 'process'])->name('process');
        Route::post('/confirm', [VideoMemoController::class, 'confirm'])->name('confirm');
    });

    // Memos — document
    Route::prefix('memos/document')->name('memos.document.')->group(function () {
        Route::post('/process', [DocumentMemoController::class, 'process'])->name('process');
        Route::post('/confirm', [DocumentMemoController::class, 'confirm'])->name('confirm');
    });

    // Memos — list, search, CRUD
    Route::prefix('memos')->name('memos.')->group(function () {
        Route::get('/recent',             [MemoController::class, 'recent'])->name('recent');
        Route::post('/search',            [MemoController::class, 'search'])->name('search');
        Route::post('/search/synonyms',   [MemoController::class, 'synonyms'])->name('search.synonyms');
        Route::get('/search/authors',     [MemoController::class, 'authors'])->name('search.authors');
        Route::post('/upload',            [MemoController::class, 'upload'])->name('upload');
        Route::get('/{id}',              [MemoController::class, 'show'])->name('show');
        Route::get('/{id}/file',         [MemoController::class, 'file'])->name('file');
        Route::patch('/{id}',            [MemoController::class, 'update'])->name('update');
        Route::delete('/{id}',           [MemoController::class, 'destroy'])->name('destroy');
    });

    // Groups
    Route::post('/groups',                        [GroupController::class, 'store'])->name('groups.store');
    Route::get('/groups/{id}/owner-panel',        [GroupController::class, 'ownerPanel'])->name('groups.owner-panel');
    Route::post('/groups/{id}/invites',           [GroupController::class, 'invite'])->name('groups.invites.store');
    Route::post('/group-invites/accept',          [GroupController::class, 'acceptInvite'])->name('group-invites.accept');

    // Etapa 2 — Auth routes added here
    // Etapa 8+ — Context, admin routes added progressively

});
