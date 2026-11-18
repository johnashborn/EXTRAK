<?php

namespace Tests\Feature;

use App\Enums\CopyType;
use App\Enums\DocumentKind;
use App\Enums\ServiceState;
use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\Service;
use App\Models\User;
use App\Services\ReportReading\NullReportReader;
use App\Services\ReportReading\ReportReader;
use App\Services\ServiceCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Concerns\ServiceFixtures;
use Tests\TestCase;

class SaveServiceTest extends TestCase
{
    use RefreshDatabase;
    use ServiceFixtures;

    private User $uploader;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        $this->withoutVite();

        $this->setUpReferenceData();
        $this->uploader = User::factory()->campusUploader($this->balilihan)->create();
    }

    /** The code the config would build for a campus, year and sequence number. */
    private function expectedCode(string $campus, int $year, int $seq): string
    {
        return strtr(config('extrack.code.format'), [
            '{PREFIX}' => config("extrack.campus_prefixes.$campus"),
            '{YEAR}' => $year,
            '{SEQ}' => str_pad((string) $seq, (int) config('extrack.code.seq_digits'), '0', STR_PAD_LEFT),
        ]);
    }

    private function storeDraft(array $overrides = []): Service
    {
        $this->actingAs($this->uploader)
            ->postJson(route('services.store'), $this->fullPayload(array_merge(['intent' => 'draft'], $overrides)))
            ->assertOk();

        return Service::latest('id')->firstOrFail();
    }

    public function test_draft_needs_only_report_and_title(): void
    {
        $required = config('extrack.draft.required');

        $this->actingAs($this->uploader)
            ->postJson(route('services.store'), $this->payloadWith($required, 'draft'))
            ->assertOk()
            ->assertJsonPath('data.redirect', route('services.show', Service::firstOrFail()));

        $service = Service::firstOrFail();
        $this->assertSame(ServiceState::Draft, $service->state);
        $this->assertNull($service->code);
        $this->assertNotNull($service->reportDocument);

        // Every field the draft rule names is needed on its own.
        foreach ($required as $field) {
            $this->actingAs($this->uploader)
                ->postJson(route('services.store'), $this->payloadWith(array_diff($required, [$field]), 'draft'))
                ->assertStatus(422)
                ->assertJsonValidationErrors($field);
        }
    }

    public function test_publish_requires_every_configured_field(): void
    {
        $draft = $this->storeDraft(['narrative' => null]);

        $this->actingAs($this->uploader)
            ->postJson(route('services.publish', $draft))
            ->assertStatus(422)
            ->assertJsonValidationErrors('narrative');

        $this->assertSame(ServiceState::Draft, $draft->fresh()->state);

        // Publishing straight from the form needs every configured field too.
        foreach (config('extrack.publish.required') as $field) {
            $this->actingAs($this->uploader)
                ->postJson(route('services.store'), $this->fullPayload([$field => null]))
                ->assertStatus(422)
                ->assertJsonValidationErrors($field);
        }
    }

    public function test_publish_rules_follow_config(): void
    {
        $draft = $this->storeDraft(['narrative' => null]);

        config(['extrack.publish.required' => array_values(array_diff(config('extrack.publish.required'), ['narrative']))]);

        $this->actingAs($this->uploader)
            ->postJson(route('services.publish', $draft))
            ->assertOk()
            ->assertJsonPath('data.redirect', route('services.show', $draft));

        $this->assertSame(ServiceState::Published, $draft->fresh()->state);
    }

    public function test_published_record_gets_code_from_start_year(): void
    {
        $draft = $this->storeDraft(['start_date' => '2026-03-02', 'end_date' => '2026-03-05']);

        $this->actingAs($this->uploader)->postJson(route('services.publish', $draft))->assertOk();

        $service = $draft->fresh();
        $this->assertSame($this->expectedCode('Balilihan', 2026, 1), $service->code);
        $this->assertSame($this->uploader->id, $service->published_by);
        $this->assertNotNull($service->published_at);

        $this->actingAs($this->uploader)
            ->putJson(route('services.update', $service), $this->fullPayload([
                'report_file' => null, 'report_copy_type' => null,
                'start_date' => '2027-01-10', 'end_date' => '2027-01-12',
            ]))
            ->assertOk();

        $service->refresh();
        $this->assertSame('2027-01-10', $service->start_date->toDateString());
        $this->assertSame($this->expectedCode('Balilihan', 2026, 1), $service->code);
    }

    public function test_publish_retries_once_when_the_code_is_taken(): void
    {
        $taken = $this->expectedCode('Balilihan', 2026, 1);
        Service::factory()->for($this->balilihan)->published()->create(['code' => $taken]);
        $draft = $this->storeDraft();

        $fresh = $this->expectedCode('Balilihan', 2026, 2);
        $this->mock(ServiceCodeGenerator::class)
            ->shouldReceive('next')->twice()->andReturn($taken, $fresh);

        $this->actingAs($this->uploader)->postJson(route('services.publish', $draft))->assertOk();

        $this->assertSame($fresh, $draft->fresh()->code);
    }

    public function test_code_can_be_assigned_when_the_draft_is_saved(): void
    {
        config(['extrack.code.assign_on' => 'draft']);

        $draft = $this->storeDraft(['start_date' => '2026-03-02', 'end_date' => '2026-03-05']);
        $this->assertSame($this->expectedCode('Balilihan', 2026, 1), $draft->code);

        $this->actingAs($this->uploader)->postJson(route('services.publish', $draft))->assertOk();
        $this->assertSame($this->expectedCode('Balilihan', 2026, 1), $draft->fresh()->code);
    }

    public function test_report_reader_is_chosen_by_config(): void
    {
        $this->assertInstanceOf(NullReportReader::class, app(ReportReader::class));

        config(['extrack.report_reader' => 'template']);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not implemented yet');
        app(ReportReader::class);
    }

    public function test_files_are_removed_when_saving_fails(): void
    {
        $this->mock(ReportReader::class)->shouldReceive('read')->andThrow(new RuntimeException('Reader failed'));

        $this->actingAs($this->uploader)
            ->postJson(route('services.store'), $this->fullPayload(['intent' => 'draft', 'attachments' => [
                ['file' => $this->pdf('letter.pdf'), 'kind' => 'letter', 'copy_type' => 'soft'],
            ]]))
            ->assertStatus(500);

        $this->assertSame(0, Service::withoutGlobalScopes()->count());
        $this->assertSame(0, Document::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_upload_rejects_file_whose_content_is_not_its_extension(): void
    {
        $fake = UploadedFile::fake()->createWithContent('report.pdf', "MZ\x90\x00\x03\x00\x00\x00\x04\x00\x00\x00\xFF\xFF\x00\x00This program cannot be run in DOS mode.");

        $this->actingAs($this->uploader)
            ->postJson(route('services.store'), $this->fullPayload(['intent' => 'draft', 'report_file' => $fake]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('report_file');

        $this->assertSame(0, Service::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_allowed_types_follow_config(): void
    {
        config(['extrack.files.report_types' => array_values(array_diff(config('extrack.files.report_types'), ['pdf']))]);

        $this->actingAs($this->uploader)
            ->postJson(route('services.store'), $this->fullPayload(['intent' => 'draft']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('report_file');
    }

    public function test_file_over_size_limit_is_rejected(): void
    {
        $kilobytes = ((int) config('extrack.files.max_mb') + 1) * 1024;
        $big = UploadedFile::fake()->create('big.pdf', $kilobytes, 'application/pdf');

        $this->actingAs($this->uploader)
            ->postJson(route('services.store'), $this->fullPayload(['intent' => 'draft', 'report_file' => $big]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('report_file');

        $this->actingAs($this->uploader)
            ->postJson(route('services.store'), $this->fullPayload(['intent' => 'draft', 'attachments' => [
                ['file' => UploadedFile::fake()->create('big.pdf', $kilobytes, 'application/pdf'), 'kind' => 'photo', 'copy_type' => 'soft'],
            ]]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('attachments.0.file');

        $this->assertSame(0, Service::count());
    }

    public function test_oversized_post_shows_friendly_error(): void
    {
        Route::middleware('web')->post('/_test/too-large', fn () => throw new PostTooLargeException);
        $message = 'This file is larger than the '.config('extrack.files.max_mb').' MB limit';

        $this->actingAs($this->uploader)
            ->from(route('services.create'))
            ->post('/_test/too-large')
            ->assertRedirect(route('services.create'))
            ->assertSessionHasErrors(['upload' => $message]);

        $this->actingAs($this->uploader)
            ->postJson('/_test/too-large')
            ->assertStatus(413)
            ->assertJsonPath('message', $message);
    }

    public function test_real_oversized_post_redirects_back_with_the_message(): void
    {
        $limit = ini_get('post_max_size');
        if ($limit === false || (int) $limit === 0) {
            $this->markTestSkipped('post_max_size is unlimited on this machine.');
        }

        // Goes through the real ValidatePostSize middleware. It must run inside the session (or the
        // flashed message is lost). Tests skip the CSRF check, so its ordering is covered by bootstrap/app.php.
        $this->actingAs($this->uploader)
            ->from(route('services.create'))
            ->call('POST', route('services.store'), [], [], [], ['CONTENT_LENGTH' => PHP_INT_MAX])
            ->assertRedirect(route('services.create'))
            ->assertSessionHasErrors('upload');
    }

    public function test_barangay_outside_chosen_municipality_is_rejected(): void
    {
        $this->actingAs($this->uploader)
            ->postJson(route('services.store'), $this->fullPayload(['barangay_ids' => [$this->otherBarangay->id]]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('barangay_ids.0');

        $this->assertSame(0, Service::count());
    }

    public function test_dates_and_people_reached_are_checked(): void
    {
        $this->actingAs($this->uploader)
            ->postJson(route('services.store'), $this->fullPayload(['start_date' => '2026-03-05', 'end_date' => '2026-03-01', 'beneficiaries' => -1]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['end_date', 'beneficiaries']);
    }

    public function test_files_are_stored_privately_with_random_names(): void
    {
        $service = $this->storeDraft(['report_file' => $this->pdf('Balilihan mushroom report.pdf'), 'attachments' => [
            ['file' => UploadedFile::fake()->image('Attendance day 1.png'), 'kind' => 'attendance', 'copy_type' => 'scanned'],
        ]]);

        $documents = $service->documents()->get();
        $this->assertCount(2, $documents);

        foreach ($documents as $document) {
            $this->assertStringStartsWith("documents/{$service->id}/", $document->path);
            $this->assertStringNotContainsString(pathinfo($document->original_name, PATHINFO_FILENAME), $document->path);
            Storage::disk('local')->assertExists($document->path);
        }

        $attendance = $documents->firstWhere('kind', DocumentKind::Attendance);
        $this->assertSame('Attendance day 1.png', $attendance->original_name);
        $this->assertSame(CopyType::Scanned, $attendance->copy_type);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_record_campus_is_always_the_uploaders(): void
    {
        $service = $this->storeDraft(['campus_id' => $this->calape->id]);

        $this->assertSame($this->balilihan->id, $service->campus_id);
        $this->assertSame($this->uploader->id, $service->encoded_by);
    }

    public function test_reader_suggestions_are_stored(): void
    {
        $this->assertSame([], $this->storeDraft()->reader_suggestions);
    }

    public function test_report_can_be_replaced_but_not_removed(): void
    {
        $service = $this->storeDraft();
        $old = $service->reportDocument;

        $this->actingAs($this->uploader)
            ->putJson(route('services.update', $service), $this->fullPayload([
                'intent' => 'draft', 'report_file' => null, 'report_copy_type' => null, 'remove_document_ids' => [$old->id],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('remove_document_ids');
        $this->assertTrue(Document::whereKey($old->id)->exists());

        $this->actingAs($this->uploader)
            ->putJson(route('services.update', $service), $this->fullPayload([
                'intent' => 'draft', 'report_file' => $this->pdf('new report.pdf'), 'report_copy_type' => 'scanned',
            ]))
            ->assertOk();

        $reports = $service->documents()->where('kind', DocumentKind::AccomplishmentReport)->get();
        $this->assertCount(1, $reports);
        $this->assertSame('new report.pdf', $reports->first()->original_name);
        Storage::disk('local')->assertMissing($old->path);
        Storage::disk('local')->assertExists($reports->first()->path);
    }

    public function test_supporting_files_can_be_added_and_removed(): void
    {
        $service = $this->storeDraft(['attachments' => [
            ['file' => $this->pdf('letter.pdf'), 'kind' => 'letter', 'copy_type' => 'soft'],
        ]]);
        $letter = $service->documents()->where('kind', DocumentKind::Letter)->firstOrFail();

        $this->actingAs($this->uploader)
            ->putJson(route('services.update', $service), $this->fullPayload([
                'intent' => 'draft', 'report_file' => null, 'report_copy_type' => null,
                'remove_document_ids' => [$letter->id],
                'attachments' => [['file' => $this->pdf('moa.pdf'), 'kind' => 'moa', 'copy_type' => 'scanned']],
            ]))
            ->assertOk();

        $this->assertFalse(Document::whereKey($letter->id)->exists());
        Storage::disk('local')->assertMissing($letter->path);
        $this->assertTrue($service->documents()->where('kind', DocumentKind::Moa)->exists());
    }

    public function test_discard_removes_draft_and_files(): void
    {
        $service = $this->storeDraft(['attachments' => [
            ['file' => $this->pdf('letter.pdf'), 'kind' => 'letter', 'copy_type' => 'soft'],
        ]]);
        $this->assertCount(2, Storage::disk('local')->allFiles());

        $this->actingAs($this->uploader)
            ->deleteJson(route('services.discard', $service))
            ->assertOk();

        $this->assertNull(Service::withoutGlobalScopes()->withTrashed()->find($service->id));
        $this->assertSame(0, Document::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_published_record_cannot_be_discarded(): void
    {
        $service = $this->completeService($this->balilihan, ServiceState::Published);

        $this->actingAs($this->uploader)->deleteJson(route('services.discard', $service))->assertForbidden();
        $this->assertTrue(Service::whereKey($service->id)->exists());
    }

    public function test_every_action_writes_activity_log(): void
    {
        $service = $this->storeDraft();
        $this->assertSame(['created', 'document_added'], ActivityLog::orderBy('id')->pluck('action')->all());

        $this->actingAs($this->uploader)
            ->putJson(route('services.update', $service), $this->fullPayload([
                'intent' => 'draft', 'report_file' => null, 'report_copy_type' => null,
                'title' => 'Mushroom growing, second batch', 'beneficiaries' => 55,
            ]))
            ->assertOk();

        $updated = ActivityLog::where('action', 'updated')->sole();
        $this->assertEqualsCanonicalizing(['title', 'beneficiaries'], array_keys($updated->changes));
        $this->assertSame(['from' => 40, 'to' => 55], $updated->changes['beneficiaries']);

        $this->actingAs($this->uploader)
            ->putJson(route('services.update', $service), $this->fullPayload([
                'intent' => 'draft', 'report_file' => null, 'report_copy_type' => null, 'title' => 'Mushroom growing, second batch', 'beneficiaries' => 55,
                'attachments' => [['file' => $this->pdf('letter.pdf'), 'kind' => 'letter', 'copy_type' => 'soft']],
            ]))
            ->assertOk();
        $this->assertSame(1, ActivityLog::where('action', 'updated')->count(), 'nothing changed, so no second "updated"');
        $this->assertSame(2, ActivityLog::where('action', 'document_added')->count());

        $this->actingAs($this->uploader)->postJson(route('services.publish', $service))->assertOk();
        $this->assertSame($service->fresh()->code, ActivityLog::where('action', 'published')->sole()->changes['code']);

        $draft = $this->storeDraft();
        $this->actingAs($this->uploader)->deleteJson(route('services.discard', $draft))->assertOk();
        $discarded = ActivityLog::where('action', 'discarded')->sole();
        $this->assertSame($draft->id, $discarded->subject_id);

        ActivityLog::all()->each(fn (ActivityLog $log) => $this->assertSame($this->uploader->id, $log->user_id));
    }

    public function test_plain_form_post_works_without_javascript(): void
    {
        $this->actingAs($this->uploader)
            ->post(route('services.store'), $this->fullPayload(['intent' => 'draft']))
            ->assertRedirect(route('services.show', Service::firstOrFail()));

        $this->actingAs($this->uploader)
            ->from(route('services.create'))
            ->post(route('services.store'), $this->fullPayload(['intent' => 'draft', 'title' => null]))
            ->assertRedirect(route('services.create'))
            ->assertSessionHasErrors('title');
    }

    public function test_form_and_record_pages_render(): void
    {
        $this->actingAs($this->uploader)->get(route('services.create'))
            ->assertOk()
            ->assertSee('Save draft')
            ->assertSee('Publish record');

        $service = $this->storeDraft();

        $this->actingAs($this->uploader)->get(route('services.edit', $service))
            ->assertOk()
            ->assertSee('Discard draft')
            ->assertSee('report.pdf');

        $this->actingAs($this->uploader)->get(route('services.show', $service))
            ->assertOk()
            ->assertSee($service->title)
            ->assertSee('report.pdf');
    }
}
