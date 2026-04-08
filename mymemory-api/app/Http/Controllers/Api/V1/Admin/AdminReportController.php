<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HardDeleteMonthRequest;
use App\Models\Memo;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminReportController extends Controller
{
    /**
     * GET /api/v1/admin/cost-report
     *
     * Returns memo creation counts grouped by type for the requested period.
     * Query params: date_from, date_to, media_type, plan_id
     */
    public function costReport(Request $request): JsonResponse
    {
        $dateFrom  = $request->query('date_from');
        $dateTo    = $request->query('date_to');
        $mediaType = $request->query('media_type');
        $planId    = $request->query('plan_id');

        $query = Memo::query()
            ->join('users', 'memos.user_id', '=', 'users.id')
            ->select(
                'memos.type',
                DB::raw('COUNT(*) as memo_count'),
            )
            ->groupBy('memos.type')
            ->orderBy('memos.type');

        if ($dateFrom) $query->where('memos.created_at', '>=', $dateFrom);
        if ($dateTo)   $query->where('memos.created_at', '<=', $dateTo . ' 23:59:59');
        if ($mediaType && $mediaType !== 'all') $query->where('memos.type', $mediaType);
        if ($planId)   $query->where('users.subscription_plan_id', (int) $planId);

        $rows = $query->get();

        $totalMemos = $rows->sum('memo_count');

        return ApiResponse::success([
            'rows'        => $rows->map(fn ($r) => [
                'type'       => $r->type,
                'memo_count' => (int) $r->memo_count,
            ])->values(),
            'total_memos' => (int) $totalMemos,
            'period'      => ['from' => $dateFrom, 'to' => $dateTo],
        ]);
    }

    /**
     * GET /api/v1/admin/soft-deleted-memos/monthly-summary
     *
     * Returns a list of {year, month, count, types} for soft-deleted memos.
     */
    public function softDeletedSummary(): JsonResponse
    {
        $rows = Memo::onlyTrashed()
            ->select(
                DB::raw('EXTRACT(YEAR  FROM deleted_at)::int AS year'),
                DB::raw('EXTRACT(MONTH FROM deleted_at)::int AS month'),
                'type',
                DB::raw('COUNT(*) as count'),
            )
            ->groupBy(DB::raw('EXTRACT(YEAR FROM deleted_at)'), DB::raw('EXTRACT(MONTH FROM deleted_at)'), 'type')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        // Group by year-month
        $grouped = [];
        foreach ($rows as $row) {
            $key = "{$row->year}-{$row->month}";
            if (!isset($grouped[$key])) {
                $grouped[$key] = ['year' => $row->year, 'month' => $row->month, 'total' => 0, 'by_type' => []];
            }
            $grouped[$key]['total']          += (int) $row->count;
            $grouped[$key]['by_type'][$row->type] = (int) $row->count;
        }

        return ApiResponse::success(array_values($grouped));
    }

    /**
     * DELETE /api/v1/admin/soft-deleted-memos/hard-delete-month
     *
     * Permanently delete all soft-deleted memos for a given year-month.
     */
    public function hardDeleteMonth(HardDeleteMonthRequest $request): JsonResponse
    {
        $year  = (int) $request->input('year');
        $month = (int) $request->input('month');

        $count = Memo::onlyTrashed()
            ->whereYear('deleted_at', $year)
            ->whereMonth('deleted_at', $month)
            ->forceDelete();

        return ApiResponse::success(['deleted_count' => $count]);
    }
}
