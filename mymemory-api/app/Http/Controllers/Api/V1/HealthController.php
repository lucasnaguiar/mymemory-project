<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $dbStatus = $this->checkDatabase();

        return ApiResponse::success([
            'status' => $dbStatus ? 'ok' : 'degraded',
            'database' => $dbStatus ? 'ok' : 'unreachable',
            'version' => 'v1',
        ]);
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
