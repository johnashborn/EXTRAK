<?php

namespace App\Services\ReportReading;

use App\Models\Document;

/**
 * Suggests nothing. Used until the template reader exists.
 */
class NullReportReader implements ReportReader
{
    public function read(Document $report): ReportFields
    {
        return new ReportFields;
    }
}
