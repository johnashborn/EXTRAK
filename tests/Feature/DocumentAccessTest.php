<?php

namespace Tests\Feature;

use App\Enums\ServiceState;
use App\Models\Campus;
use App\Models\Document;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function document(Campus $campus, bool $published, array $attributes = []): Document
    {
        $service = Service::factory()->for($campus)->create(['state' => $published ? ServiceState::Published : ServiceState::Draft]);
        $attributes += ['original_name' => 'report.pdf', 'mime' => 'application/pdf'];
        $document = Document::factory()->for($service)->create($attributes + ['path' => "documents/{$service->id}/abc.bin"]);
        Storage::disk('local')->put($document->path, 'file contents');

        return $document;
    }

    private function campus(string $name = 'Balilihan'): Campus
    {
        return Campus::create(['name' => $name, 'municipality' => $name]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $document = $this->document($this->campus(), true);

        $this->get(route('documents.show', $document))->assertRedirect(route('login'));
        $this->get(route('documents.download', $document))->assertRedirect(route('login'));
    }

    public function test_other_campus_cannot_open_draft_files(): void
    {
        $document = $this->document($this->campus(), false);
        $other = User::factory()->campusUploader($this->campus('Calape'))->create();

        $this->actingAs($other)->get(route('documents.show', $document))->assertForbidden();
        $this->actingAs($other)->get(route('documents.download', $document))->assertForbidden();
    }

    public function test_hidden_draft_document_is_denied_not_errored(): void
    {
        $campus = $this->campus();
        $document = $this->document($campus, false);
        Storage::disk('local')->delete($document->path);

        // A director cannot see drafts: the service is null, and denial comes before the missing-file check.
        $this->actingAs(User::factory()->campusDirector($campus)->create())
            ->get(route('documents.show', $document))
            ->assertForbidden();
    }

    public function test_own_campus_uploader_opens_own_draft_file(): void
    {
        $campus = $this->campus();
        $document = $this->document($campus, false);

        $this->actingAs(User::factory()->campusUploader($campus)->create())
            ->get(route('documents.show', $document))
            ->assertOk();
    }

    public function test_any_role_opens_published_files(): void
    {
        $campus = $this->campus();
        $document = $this->document($campus, true);

        foreach ([User::factory()->president(), User::factory()->campusUploader($campus), User::factory()->campusDirector($campus)] as $factory) {
            $this->actingAs($factory->create())->get(route('documents.show', $document))->assertOk();
        }
    }

    public function test_missing_file_is_a_404(): void
    {
        $document = $this->document($this->campus(), true);
        Storage::disk('local')->delete($document->path);

        $this->actingAs(User::factory()->president()->create())
            ->get(route('documents.show', $document))
            ->assertNotFound();
    }

    public function test_pdf_opens_inline_and_docx_downloads(): void
    {
        $campus = $this->campus();
        $pdf = $this->document($campus, true);
        $docx = $this->document($campus, true, [
            'original_name' => 'report.docx',
            'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
        $this->actingAs(User::factory()->president()->create());

        $response = $this->get(route('documents.show', $pdf));
        $this->assertStringStartsWith('inline', $response->headers->get('Content-Disposition'));
        $response->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertStringStartsWith('attachment', $this->get(route('documents.show', $docx))->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('attachment', $this->get(route('documents.download', $pdf))->headers->get('Content-Disposition'));
    }

    public function test_download_keeps_non_ascii_original_name(): void
    {
        $name = 'Accomplishment Report – Sta. Niño.pdf';
        $document = $this->document($this->campus(), true, ['original_name' => $name]);

        $response = $this->actingAs(User::factory()->president()->create())->get(route('documents.download', $document));

        $header = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString("filename*=utf-8''".rawurlencode($name), $header);
        $this->assertStringStartsWith('attachment', $header);
    }

    public function test_view_and_download_are_logged(): void
    {
        $document = $this->document($this->campus(), true);
        $user = User::factory()->president()->create();

        $this->actingAs($user)->get(route('documents.show', $document))->assertOk();
        $this->actingAs($user)->get(route('documents.download', $document))->assertOk();

        foreach (['document_viewed', 'document_downloaded'] as $action) {
            $this->assertDatabaseHas('activity_log', [
                'user_id' => $user->id,
                'action' => $action,
                'subject_type' => $document->getMorphClass(),
                'subject_id' => $document->id,
            ]);
        }
    }

    public function test_svg_is_never_served_inline(): void
    {
        $document = $this->document($this->campus(), true, ['original_name' => 'logo.svg', 'mime' => 'image/svg+xml']);

        $response = $this->actingAs(User::factory()->president()->create())->get(route('documents.show', $document));

        $this->assertStringStartsWith('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_responses_carry_a_content_security_policy(): void
    {
        $campus = $this->campus();
        $pdf = $this->document($campus, true);
        $png = $this->document($campus, true, ['original_name' => 'photo.png', 'mime' => 'image/png']);
        $this->actingAs(User::factory()->president()->create());

        foreach (['documents.show', 'documents.download'] as $route) {
            $header = $this->get(route($route, $pdf))->headers->get('Content-Security-Policy');
            $this->assertSame("default-src 'none'; style-src 'unsafe-inline'", $header);
            $this->assertStringNotContainsString('sandbox', $header);

            $this->get(route($route, $png))->assertHeader(
                'Content-Security-Policy',
                "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox"
            );
        }
    }

    public function test_download_survives_slashes_percent_and_newlines_in_name(): void
    {
        $document = $this->document($this->campus(), true, ['original_name' => "100% done/a\\b\r\n.pdf"]);

        $response = $this->actingAs(User::factory()->president()->create())->get(route('documents.download', $document));

        $response->assertOk();
        $header = $response->headers->get('Content-Disposition');
        $this->assertStringStartsWith('attachment', $header);
        $this->assertStringNotContainsString("\n", $header);
        $this->assertStringNotContainsString("\r", $header);
        $this->assertStringContainsString("filename*=utf-8''".rawurlencode('100% done-a-b.pdf'), $header);
    }
}
