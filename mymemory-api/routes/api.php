<?php

use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\Memo\TextMemoController;
use App\Http\Controllers\Api\V1\Memo\UrlMemoController;
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

    // Etapa 2 — Auth routes added here
    // Etapa 5+ — Image/Audio/Video/Document routes added progressively

});
