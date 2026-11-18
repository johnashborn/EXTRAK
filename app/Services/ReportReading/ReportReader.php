<?php

namespace App\Services\ReportReading;

use App\Models\Document;

/**
 * Reads an uploaded accomplishment report and suggests values for the form fields.
 * The prototype binds NullReportReader; a template or AI reader replaces it later.
 */
interface ReportReader
{
    public function read(Document $report): ReportFields;
}
