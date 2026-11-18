<?php

namespace App\Services;

use App\Models\Campus;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

/**
 * Builds the next service code, e.g. BAL-2026-003, from config('extrack.code').
 */
class ServiceCodeGenerator
{
    public function next(Campus $campus, int $year): string
    {
        return DB::transaction(function () use ($campus, $year) {
            // Serialise concurrent callers for this campus.
            Campus::whereKey($campus->id)->lockForUpdate()->first();

            $prefix = config('extrack.campus_prefixes')[$campus->name] ?? strtoupper(substr($campus->name, 0, 3));
            $digits = (int) config('extrack.code.seq_digits');
            $fixed = strtr(config('extrack.code.format'), ['{PREFIX}' => $prefix, '{YEAR}' => $year]);

            // LIKE narrows the rows; the regex makes sure only real sequence numbers count.
            $like = str_replace('{SEQ}', '%', $fixed);
            $regex = '/^'.str_replace(preg_quote('{SEQ}', '/'), '(\d+)', preg_quote($fixed, '/')).'$/';

            // Soft-deleted rows count, so a code is never handed out twice.
            $highest = Service::withoutGlobalScopes()->withTrashed()
                ->where('code', 'like', $like)
                ->pluck('code')
                ->map(fn (string $code) => preg_match($regex, $code, $m) ? (int) $m[1] : 0)
                ->max() ?? 0;

            return str_replace('{SEQ}', str_pad((string) ($highest + 1), $digits, '0', STR_PAD_LEFT), $fixed);
        });
    }
}
