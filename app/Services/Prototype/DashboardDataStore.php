<?php

namespace App\Services\Prototype;

use App\Models\AccomplishmentReport;
use App\Models\Attachment;
use App\Models\Barangay;
use App\Models\BeneficiarySector;
use App\Models\BudgetChange;
use App\Models\Campus;
use App\Models\CampusBudget;
use App\Models\Category;
use App\Models\DashboardNotification;
use App\Models\ExtensionRequest;
use App\Models\Official;
use App\Models\OverlapFlag;
use App\Models\PrototypeFile;
use App\Models\RequestEvent;
use App\Models\RequestRemark;
use App\Models\Setting;
use App\Models\UniversityBudget;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Keeps the dashboards' data in the extension tables.
 *
 * The prototype dashboards still load and save everything at once, in the shape extrack.js
 * works with. export() builds that shape from the tables, and import() writes a saved copy
 * back into them, row by row. Each save bumps a version number, so other open dashboards
 * can tell that something changed. Last save wins.
 */
class DashboardDataStore
{
    private const TIME = 'Y-m-d\TH:i';

    /** Lookups filled while importing, so each name is found or created once. */
    private array $ids = [];

    public function version(): int
    {
        return (int) Setting::valueOf('data_version', 0);
    }

    /** Everything the dashboards show, or null before anything was saved (the dashboards then start from sample data). */
    public function export(): ?array
    {
        if (Setting::valueOf('schema') === null) {
            return null;
        }
        $year = (int) Setting::valueOf('year');
        $campuses = Campus::pluck('name', 'id');
        $officials = Official::pluck('key', 'id');
        $key = fn (?int $id) => $id ? ($officials[$id] ?? null) : null;

        $requests = ExtensionRequest::with([
            'category', 'barangays', 'sectors', 'events', 'remarks', 'overlap', 'report.photos', 'attachments',
        ])->orderBy('position')->get();

        return [
            'schema' => (int) Setting::valueOf('schema'),
            'year' => $year,
            'nextNo' => (int) Setting::valueOf('next_request_no'),
            'universityBudget' => $this->number(UniversityBudget::where('year', $year)->value('amount') ?? 0),
            'allocations' => CampusBudget::where('year', $year)->get()
                ->mapWithKeys(fn ($b) => [$campuses[$b->campus_id] => $this->number($b->allocation)])->all(),
            'budgetLog' => BudgetChange::orderBy('position')->get()->map(fn ($c) => [
                'at' => $c->changed_at->format(self::TIME),
                'by' => $key($c->changed_by),
                'campus' => $c->campus_id ? $campuses[$c->campus_id] : null,
                'year' => $c->year,
                'from' => $this->number($c->from_amount),
                'to' => $this->number($c->to_amount),
                'kind' => $c->kind,
                'reason' => $c->reason,
            ])->all(),
            'extraSectors' => BeneficiarySector::where('is_custom', true)->orderBy('position')->pluck('name')->all(),
            'notifications' => DashboardNotification::orderBy('position')->get()->map(fn ($n) => [
                'id' => $n->ref,
                'to' => $n->recipient,
                'at' => $n->sent_at->format(self::TIME),
                'kind' => $n->kind,
                'read' => $n->is_read,
                'text' => $n->text,
                'ref' => $n->request_ref,
            ])->all(),
            'requests' => $requests->map(fn ($r) => $this->exportRequest($r, $campuses, $key))->all(),
        ];
    }

    private function exportRequest(ExtensionRequest $r, $campuses, callable $key): array
    {
        $overlap = $r->overlap ? array_filter([
            'score' => $r->overlap->score,
            'match' => $r->overlap->match,
            'terms' => $r->overlap->terms,
            'resolution' => $r->overlap->resolution,
            'resolvedBy' => $key($r->overlap->resolved_by),
        ], fn ($v, $k) => $k !== 'resolvedBy' || $v !== null, ARRAY_FILTER_USE_BOTH) : null;

        $report = $r->report ? array_filter([
            'actualStart' => $this->date($r->report->actual_start),
            'actualEnd' => $this->date($r->report->actual_end),
            'actualBudget' => $this->number($r->report->actual_budget),
            'actualBeneficiaries' => $r->report->actual_beneficiaries,
            'narrative' => $r->report->narrative ?? '',
            'photos' => $r->report->photos->map(fn ($p) => array_filter([
                'name' => $p->name,
                'src' => $p->url,
                'fileId' => $p->file_id,
            ], fn ($v) => $v !== null))->all(),
            'encodedBy' => $key($r->report->encoded_by),
        ], fn ($v, $k) => $k !== 'encodedBy' || $v !== null, ARRAY_FILTER_USE_BOTH) : null;

        return [
            'id' => $r->ref,
            'code' => $r->code,
            'campus' => $campuses[$r->campus_id],
            'requestedBy' => $key($r->requested_by),
            'encodedBy' => $key($r->encoded_by),
            'title' => $r->title,
            'description' => $r->description ?? '',
            'category' => $r->category?->name ?? '',
            'municipality' => $r->municipality,
            'barangays' => $r->barangays->pluck('name')->all(),
            'start' => $this->date($r->start_date),
            'end' => $this->date($r->end_date),
            'budget' => $this->number($r->proposed_budget),
            'beneficiaries' => $r->expected_beneficiaries,
            'sectors' => $r->sectors->pluck('name')->all(),
            'partners' => $r->partners,
            'status' => $r->status,
            'overlap' => $overlap,
            'events' => $r->events->map(fn ($e) => [
                'at' => $e->happened_at->format(self::TIME),
                'by' => $key($e->official_id),
                'from' => $e->from_status,
                'to' => $e->to_status,
                'remark' => $e->remark,
            ])->all(),
            'remarks' => $r->remarks->map(fn ($m) => [
                'at' => $m->made_at->format(self::TIME),
                'by' => $key($m->official_id),
                'body' => $m->body,
            ])->all(),
            'report' => $report,
            'attachments' => $r->attachments->map(fn ($a) => array_filter([
                'id' => $a->ref,
                'kind' => $a->kind,
                'name' => $a->name,
                'type' => $a->mime_type,
                'size' => $a->size,
                'src' => $a->url,
                'sample' => $a->is_sample ?: null,
                'by' => $key($a->uploaded_by),
                'at' => $a->uploaded_at?->format(self::TIME) ?? '',
                'fileId' => $a->file_id,
            ], fn ($v) => $v !== null))->all(),
        ];
    }

    /** Writes a saved copy of the dashboards' data into the tables and returns the new version. */
    public function import(array $data, ?User $user): int
    {
        return DB::transaction(function () use ($data, $user) {
            $this->ids = [];
            $year = (int) ($data['year'] ?? now()->year);

            Setting::put('schema', $data['schema'] ?? null);
            Setting::put('year', $year);
            Setting::put('next_request_no', $data['nextNo'] ?? 1);

            $this->importBudgets($data, $year);
            $this->importCustomSectors($data['extraSectors'] ?? []);
            $requestIds = $this->importRequests($data['requests'] ?? []);
            $this->importNotifications($data['notifications'] ?? [], $requestIds);

            $version = $this->version() + 1;
            Setting::put('data_version', $version);
            Setting::put('updated_by', $user?->id);

            return $version;
        });
    }

    private function importBudgets(array $data, int $year): void
    {
        if (isset($data['universityBudget'])) {
            UniversityBudget::updateOrCreate(['year' => $year], ['amount' => $data['universityBudget']]);
        }
        foreach ($data['allocations'] ?? [] as $campus => $amount) {
            CampusBudget::updateOrCreate(['campus_id' => $this->campusId($campus), 'year' => $year], ['allocation' => $amount]);
        }

        BudgetChange::query()->delete();
        $rows = [];
        foreach (array_values($data['budgetLog'] ?? []) as $i => $c) {
            $rows[] = [
                'year' => $c['year'] ?? $year,
                'campus_id' => empty($c['campus']) ? null : $this->campusId($c['campus']),
                'kind' => $c['kind'] ?? 'set',
                'from_amount' => $c['from'] ?? 0,
                'to_amount' => $c['to'] ?? 0,
                'reason' => $c['reason'] ?? null,
                'changed_by' => $this->officialId($c['by'] ?? null),
                'changed_at' => $this->time($c['at'] ?? null) ?? now(),
                'position' => $i,
            ];
        }
        BudgetChange::insert($rows);
    }

    private function importCustomSectors(array $names): void
    {
        $names = array_values(array_unique(array_filter(array_map('strval', $names))));
        BeneficiarySector::where('is_custom', true)->whereNotIn('name', $names)->delete();
        foreach ($names as $i => $name) {
            $sector = BeneficiarySector::firstOrCreate(['name' => $name], ['is_custom' => true]);
            if ($sector->is_custom) {
                $sector->update(['position' => 100 + $i]);
            }
        }
    }

    /** @return array<string, int> the request ids by the dashboards' id */
    private function importRequests(array $requests): array
    {
        $requests = array_values(array_filter($requests, fn ($r) => ! empty($r['id']) && ! empty($r['code'])));
        $refs = array_column($requests, 'id');

        // requests the dashboards no longer have (a deleted draft, or the demo data reset)
        $gone = ExtensionRequest::whereNotIn('ref', $refs)->pluck('id');
        if ($gone->isNotEmpty()) {
            $gone->chunk(500)->each(function ($ids) {
                $reports = AccomplishmentReport::whereIn('extension_request_id', $ids)->pluck('id');
                $this->attachmentsOf(AccomplishmentReport::class, $reports)->delete();
                $this->attachmentsOf(ExtensionRequest::class, $ids)->delete();
                ExtensionRequest::whereIn('id', $ids)->delete();
            });
        }

        $rows = [];
        foreach ($requests as $i => $r) {
            $rows[] = [
                'ref' => $r['id'],
                'code' => $r['code'],
                'campus_id' => $this->campusId($r['campus'] ?? 'Balilihan'),
                'requested_by' => $this->officialId($r['requestedBy'] ?? null),
                'encoded_by' => $this->officialId($r['encodedBy'] ?? null),
                'title' => $r['title'] ?? '',
                'description' => $r['description'] ?? null,
                'category_id' => empty($r['category']) ? null : $this->categoryId($r['category']),
                'municipality' => $r['municipality'] ?? '',
                'start_date' => $this->day($r['start'] ?? null),
                'end_date' => $this->day($r['end'] ?? null),
                'proposed_budget' => (float) ($r['budget'] ?? 0),
                'expected_beneficiaries' => (int) ($r['beneficiaries'] ?? 0),
                'partners' => $r['partners'] ?? null,
                'status' => $r['status'] ?? 'draft',
                'position' => $i,
            ];
        }
        foreach (array_chunk($rows, 200) as $chunk) {
            ExtensionRequest::upsert($chunk, ['ref'], array_diff(array_keys($chunk[0]), ['ref']));
        }
        $ids = ExtensionRequest::whereIn('ref', $refs)->pluck('id', 'ref')->all();

        $this->replaceChildren($requests, $ids);

        return $ids;
    }

    /** Rewrites the history, remarks, places, sectors, overlap, documents and reports of the saved requests. */
    private function replaceChildren(array $requests, array $ids): void
    {
        $requestIds = array_values($ids);
        RequestEvent::whereIn('extension_request_id', $requestIds)->delete();
        RequestRemark::whereIn('extension_request_id', $requestIds)->delete();
        OverlapFlag::whereIn('extension_request_id', $requestIds)->delete();
        DB::table('request_barangay')->whereIn('extension_request_id', $requestIds)->delete();
        DB::table('request_sector')->whereIn('extension_request_id', $requestIds)->delete();
        $this->attachmentsOf(ExtensionRequest::class, $requestIds)->delete();

        $events = $remarks = $overlaps = $barangays = $sectors = $attachments = [];
        $withReport = [];
        foreach ($requests as $r) {
            $id = $ids[$r['id']];
            foreach (array_values($r['events'] ?? []) as $i => $e) {
                $events[] = [
                    'extension_request_id' => $id,
                    'official_id' => $this->officialId($e['by'] ?? null),
                    'from_status' => $e['from'] ?? null,
                    'to_status' => $e['to'] ?? '',
                    'remark' => $e['remark'] ?? null,
                    'happened_at' => $this->time($e['at'] ?? null) ?? now(),
                    'position' => $i,
                ];
            }
            foreach (array_values($r['remarks'] ?? []) as $i => $m) {
                $remarks[] = [
                    'extension_request_id' => $id,
                    'official_id' => $this->officialId($m['by'] ?? null),
                    'body' => $m['body'] ?? '',
                    'made_at' => $this->time($m['at'] ?? null) ?? now(),
                    'position' => $i,
                ];
            }
            if (! empty($r['overlap'])) {
                $o = $r['overlap'];
                $overlaps[] = [
                    'extension_request_id' => $id,
                    'score' => (int) ($o['score'] ?? 0),
                    'match_code' => $o['match']['code'] ?? null,
                    'match' => json_encode($o['match'] ?? (object) []),
                    'terms' => json_encode(array_values($o['terms'] ?? [])),
                    'resolution' => $o['resolution'] ?? null,
                    'resolved_by' => $this->officialId($o['resolvedBy'] ?? null),
                ];
            }
            foreach (array_values(array_unique($r['barangays'] ?? [])) as $i => $name) {
                $barangays[] = ['extension_request_id' => $id, 'barangay_id' => $this->barangayId($r['municipality'] ?? '', $name), 'position' => $i];
            }
            foreach (array_values(array_unique($r['sectors'] ?? [])) as $i => $name) {
                $sectors[] = ['extension_request_id' => $id, 'beneficiary_sector_id' => $this->sectorId($name), 'position' => $i];
            }
            foreach (array_values($r['attachments'] ?? []) as $i => $a) {
                $attachments[] = $this->attachmentRow(ExtensionRequest::class, $id, $a, $a['kind'] ?? 'other', $i);
            }
            if (! empty($r['report'])) {
                $withReport[$id] = $r['report'];
            }
        }

        foreach (array_chunk($events, 500) as $chunk) {
            RequestEvent::insert($chunk);
        }
        RequestRemark::insert($remarks);
        OverlapFlag::insert($overlaps);
        DB::table('request_barangay')->insert($barangays);
        DB::table('request_sector')->insert($sectors);

        $this->importReports($requestIds, $withReport, $attachments);
    }

    private function importReports(array $requestIds, array $withReport, array $attachments): void
    {
        $stale = AccomplishmentReport::whereIn('extension_request_id', $requestIds)
            ->whereNotIn('extension_request_id', array_keys($withReport))->pluck('id');
        $this->attachmentsOf(AccomplishmentReport::class, $stale)->delete();
        AccomplishmentReport::whereIn('id', $stale)->delete();

        foreach ($withReport as $requestId => $p) {
            $report = AccomplishmentReport::updateOrCreate(['extension_request_id' => $requestId], [
                'actual_start' => $this->day($p['actualStart'] ?? null),
                'actual_end' => $this->day($p['actualEnd'] ?? null),
                'actual_budget' => (float) ($p['actualBudget'] ?? 0),
                'actual_beneficiaries' => (int) ($p['actualBeneficiaries'] ?? 0),
                'narrative' => $p['narrative'] ?? null,
                'encoded_by' => $this->officialId($p['encodedBy'] ?? null),
            ]);
            $this->attachmentsOf(AccomplishmentReport::class, [$report->id])->delete();
            foreach (array_values($p['photos'] ?? []) as $i => $photo) {
                $attachments[] = $this->attachmentRow(AccomplishmentReport::class, $report->id, $photo, Attachment::PHOTO, $i);
            }
        }

        foreach (array_chunk($attachments, 100) as $chunk) {
            Attachment::insert($chunk);
        }
    }

    private function attachmentRow(string $type, int $ownerId, array $a, string $kind, int $position): array
    {
        $fileId = $a['fileId'] ?? null;

        return [
            'attachable_type' => (new $type)->getMorphClass(),
            'attachable_id' => $ownerId,
            'ref' => $a['id'] ?? null,
            'kind' => $kind,
            // a file that was removed from storage is kept as a plain link rather than failing the save
            'file_id' => $fileId && $this->fileExists($fileId) ? $fileId : null,
            'name' => $a['name'] ?? 'File',
            'mime_type' => $a['type'] ?? null,
            'size' => isset($a['size']) ? (int) $a['size'] : null,
            'url' => $a['src'] ?? '',
            'is_sample' => ! empty($a['sample']),
            'uploaded_by' => $this->officialId($a['by'] ?? null),
            'uploaded_at' => $this->time($a['at'] ?? null),
            'position' => $position,
        ];
    }

    private function importNotifications(array $notifications, array $requestIds): void
    {
        DashboardNotification::query()->delete();
        $rows = [];
        $seen = [];
        foreach (array_values($notifications) as $i => $n) {
            // two notifications sent in the same millisecond can share an id; keep both
            $ref = (string) ($n['id'] ?? 'n'.$i);
            if (isset($seen[$ref])) {
                $ref .= '-'.$i;
            }
            $seen[$ref] = true;
            $rows[] = [
                'ref' => $ref,
                'recipient' => $n['to'] ?? '',
                'kind' => $n['kind'] ?? 'info',
                'text' => $n['text'] ?? '',
                'extension_request_id' => isset($n['ref']) ? ($requestIds[$n['ref']] ?? null) : null,
                'request_ref' => $n['ref'] ?? null,
                'is_read' => ! empty($n['read']),
                'sent_at' => $this->time($n['at'] ?? null) ?? now(),
                'position' => $i,
            ];
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            DashboardNotification::insert($chunk);
        }
    }

    private function attachmentsOf(string $type, $ids)
    {
        return Attachment::where('attachable_type', (new $type)->getMorphClass())->whereIn('attachable_id', $ids);
    }

    // ---------- lookups: names and keys from the dashboards, created when new ----------

    private function campusId(string $name): int
    {
        return $this->ids['campus'][$name] ??= Campus::firstOrCreate(['name' => $name], ['municipality' => $name])->id;
    }

    private function officialId(?string $key): ?int
    {
        if (! $key) {
            return null;
        }

        return $this->ids['official'][$key] ??= Official::firstOrCreate(['key' => $key], ['name' => $key, 'title' => '', 'role' => 'unknown'])->id;
    }

    private function fileExists(string $id): bool
    {
        $this->ids['file'] ??= PrototypeFile::pluck('id')->flip()->all();

        return isset($this->ids['file'][$id]);
    }

    private function categoryId(string $name): int
    {
        return $this->ids['category'][$name] ??= Category::firstOrCreate(['name' => $name])->id;
    }

    private function barangayId(string $municipality, string $name): int
    {
        return $this->ids['barangay'][$municipality.'/'.$name] ??= Barangay::firstOrCreate(['municipality' => $municipality, 'name' => $name])->id;
    }

    private function sectorId(string $name): int
    {
        return $this->ids['sector'][$name] ??= BeneficiarySector::firstOrCreate(['name' => $name], ['is_custom' => true])->id;
    }

    // ---------- values ----------

    private function day(?string $value): ?string
    {
        return $value ? Carbon::parse($value)->toDateString() : null;
    }

    private function time(?string $value): ?Carbon
    {
        return $value ? Carbon::parse($value) : null;
    }

    private function date(?CarbonInterface $value): string
    {
        return $value?->toDateString() ?? '';
    }

    /** Whole pesos as integers, as the dashboards keep them. */
    private function number($value): int|float
    {
        $n = (float) $value;

        return floor($n) == $n ? (int) $n : $n;
    }
}
