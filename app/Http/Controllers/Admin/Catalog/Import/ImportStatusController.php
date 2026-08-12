<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Models\System\ProductImport;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/** Статус последних импортов каждого типа. */
#[Group('Каталог / импорт', weight: 10)]
final readonly class ImportStatusController
{
    /** Последний импорт каждого типа. */
    public function __invoke(): JsonResponse
    {
        $latestIds = ProductImport::select('type', DB::raw('MAX(id) as id'))
            ->groupBy('type')
            ->pluck('id');

        $rows = ProductImport::whereIn('id', $latestIds)
            ->get(['type', 'processed_rows', 'error_message', 'errors', 'finished_at'])
            ->map(fn (ProductImport $i): array => [
                'type' => $i->type,
                'processed_rows' => $i->processed_rows,
                'error_message' => $i->error_message,
                'errors' => $i->errors,
                'finished_at' => $i->finished_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $rows]);
    }
}
