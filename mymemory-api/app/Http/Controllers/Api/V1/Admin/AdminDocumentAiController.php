<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\DocumentAiRouting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDocumentAiController extends Controller
{
    private const DEFAULT_CONFIG = [
        'pdf'  => 'extract_text',
        'docx' => 'extract_text',
        'msg'  => 'extract_text',
        'eml'  => 'extract_text',
        'txt'  => 'extract_text',
    ];

    /** GET /api/v1/admin/document-ai-routing */
    public function show(): JsonResponse
    {
        $record = DocumentAiRouting::first();

        if (!$record) {
            return ApiResponse::success([
                'config'        => self::DEFAULT_CONFIG,
                'using_defaults'=> true,
                'updated_by'    => null,
                'updated_at'    => null,
            ]);
        }

        return ApiResponse::success([
            'config'        => $record->config,
            'using_defaults'=> false,
            'updated_by'    => $record->updatedBy ? ['id' => $record->updatedBy->id, 'name' => $record->updatedBy->name] : null,
            'updated_at'    => $record->updated_at?->toIso8601String(),
        ]);
    }

    /** PUT /api/v1/admin/document-ai-routing */
    public function update(Request $request): JsonResponse
    {
        $config = $request->input('config');

        if (!is_array($config)) {
            return ApiResponse::validationError(['config' => ['Must be a JSON object.']], 'Validation failed.');
        }

        $record = DocumentAiRouting::firstOrNew(['id' => 1]);
        $record->fill([
            'config'              => $config,
            'updated_by_user_id'  => $request->user()->id,
        ]);
        $record->save();

        return ApiResponse::success([
            'config'        => $record->config,
            'using_defaults'=> false,
        ]);
    }
}
