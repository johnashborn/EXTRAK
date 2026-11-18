<?php

namespace App\Services;

use App\Models\Service;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Gives a record its code and saves it while the generator's campus lock is still held.
 *
 * ServiceCodeGenerator::next() locks the campus row in its own transaction. Called on its own, that
 * lock is released before the caller saves the code. Callers must therefore already be inside a
 * DB::transaction: next() then nests and its lock lasts until the caller commits, covering the save.
 */
class ServiceCodeAssigner
{
    public function __construct(private readonly ServiceCodeGenerator $codes)
    {
    }

    public function assign(Service $service, int $year): void
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Assign service codes inside a DB::transaction so the campus lock covers the save.');
        }

        $original = $service->getOriginal('code');

        for ($attempt = 1; ; $attempt++) {
            try {
                // A savepoint, so a clash rolls back only this attempt and the retry runs on a clean state.
                DB::transaction(function () use ($service, $year) {
                    $service->code = $this->codes->next($service->campus()->firstOrFail(), $year);
                    $service->save();
                });

                return;
            } catch (UniqueConstraintViolationException $e) {
                $service->code = $original;

                if ($attempt >= 2) {
                    throw $e;
                }
            }
        }
    }
}
