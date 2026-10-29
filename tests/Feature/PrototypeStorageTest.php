<?php

namespace Tests\Feature;

use App\Actions\Prototype\StorePrototypeFile;
use App\Models\PrototypeFile;
use App\Models\PrototypeState;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrototypeStorageTest extends TestCase
{
    use RefreshDatabase;

    private function sampleState(string $title = 'Tilapia training'): array
    {
        return ['schema' => 7, 'requests' => [['id' => 'r1', 'title' => $title]], 'notifications' => []];
    }

    public function test_guests_cannot_read_or_save_data_or_files(): void
    {
        $this->getJson('/prototype/api/state')->assertUnauthorized();
        $this->putJson('/prototype/api/state', ['data' => $this->sampleState()])->assertUnauthorized();
        $this->postJson('/prototype/api/files', [])->assertUnauthorized();
    }

    public function test_state_starts_empty_and_each_save_bumps_the_version(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/prototype/api/state')
            ->assertOk()->assertExactJson(['data' => null, 'version' => 0]);

        $this->actingAs($user)->putJson('/prototype/api/state', ['data' => $this->sampleState()])
            ->assertOk()->assertExactJson(['version' => 1]);
        $this->actingAs($user)->putJson('/prototype/api/state', ['data' => $this->sampleState('Mangrove planting')])
            ->assertOk()->assertExactJson(['version' => 2]);

        $this->actingAs($user)->getJson('/prototype/api/state')
            ->assertOk()
            ->assertJsonPath('version', 2)
            ->assertJsonPath('data.requests.0.title', 'Mangrove planting')
            // everything besides the requests is kept too
            ->assertJsonPath('data.schema', 7)
            ->assertJsonPath('data.notifications', []);
        $this->assertSame($user->id, PrototypeState::current()->updated_by);
    }

    public function test_state_without_requests_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->putJson('/prototype/api/state', ['data' => ['notifications' => []]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('data.requests');
    }

    public function test_the_dashboard_page_starts_from_the_saved_data(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->putJson('/prototype/api/state', ['data' => $this->sampleState('Saved on the server')]);

        $this->actingAs($user)->get('/prototype/campus-director')
            ->assertOk()
            ->assertSee('Saved on the server', false)
            ->assertSee('"version":1', false);
    }

    public function test_a_photo_can_be_uploaded_viewed_and_downloaded(): void
    {
        Storage::fake(StorePrototypeFile::DISK);
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/prototype/api/files', ['file' => UploadedFile::fake()->image('site visit.jpg', 1600, 1200)])
            ->assertCreated()
            ->assertJsonPath('name', 'site visit.jpg')
            ->assertJsonPath('type', 'image/jpeg');

        $file = PrototypeFile::findOrFail($response->json('id'));
        Storage::disk(StorePrototypeFile::DISK)->assertExists($file->path);
        $this->assertSame($user->id, $file->uploaded_by);
        $this->assertSame(route('prototype.files.show', $file, false), $response->json('url'));

        $this->actingAs($user)->get($response->json('url'))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
        $this->actingAs($user)->get($response->json('url').'?download=1')
            ->assertOk()
            ->assertDownload('site visit.jpg');
    }

    public function test_a_pdf_document_is_accepted(): void
    {
        Storage::fake(StorePrototypeFile::DISK);

        $this->actingAs(User::factory()->create())
            ->postJson('/prototype/api/files', ['file' => UploadedFile::fake()->create('Barangay letter.pdf', 300, 'application/pdf')])
            ->assertCreated()
            ->assertJsonPath('type', 'application/pdf');
    }

    public function test_unsupported_and_oversized_files_are_refused(): void
    {
        Storage::fake(StorePrototypeFile::DISK);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/prototype/api/files', ['file' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $this->actingAs($user)
            ->postJson('/prototype/api/files', ['file' => UploadedFile::fake()->create('huge.pdf', 25000, 'application/pdf')])
            ->assertUnprocessable()
            ->assertJsonPath('errors.file.0', 'This file is larger than 20 MB.');
    }

    public function test_guests_cannot_open_uploaded_files(): void
    {
        Storage::fake(StorePrototypeFile::DISK);
        $file = app(StorePrototypeFile::class)(UploadedFile::fake()->image('a.jpg'), null);

        $this->get(route('prototype.files.show', $file))->assertRedirect(route('filament.admin.auth.login'));
    }
}
