<?php

use App\Http\Controllers\Api\V1\HealthController;
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
// Authenticated routes (Sanctum stateful guard) — added in Etapa 2+
// ---------------------------------------------------------------------------
// Route::middleware('auth:sanctum')->group(function () {
//     ...
// });
