<?php

namespace Tests\Concerns;

use App\Enums\CopyType;
use App\Enums\DocumentKind;
use App\Enums\ServiceState;
use App\Models\Barangay;
use App\Models\BeneficiarySector;
use App\Models\Campus;
use App\Models\Category;
use App\Models\Document;
use App\Models\Municipality;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * Reference rows and request payloads shared by the record tests.
 */
trait ServiceFixtures
{
    protected Campus $balilihan;

    protected Campus $calape;

    protected Category $category;

    protected Municipality $municipality;

    protected Barangay $barangay;

    protected Municipality $otherMunicipality;

    protected Barangay $otherBarangay;

    protected BeneficiarySector $sector;

    protected function setUpReferenceData(): void
    {
        $this->balilihan = Campus::create(['name' => 'Balilihan', 'municipality' => 'Balilihan']);
        $this->calape = Campus::create(['name' => 'Calape', 'municipality' => 'Calape']);
        $this->category = Category::create(['name' => 'Livelihood']);
        $this->sector = BeneficiarySector::create(['name' => 'Farmers']);

        $this->municipality = Municipality::create([
            'psgc_code' => '0701205000', 'name' => 'Balilihan', 'latitude' => 9.75, 'longitude' => 123.97,
        ]);
        $this->barangay = Barangay::create([
            'psgc_code' => '0701205001', 'municipality_id' => $this->municipality->id, 'name' => 'Poblacion',
        ]);
        $this->otherMunicipality = Municipality::create([
            'psgc_code' => '0701210000', 'name' => 'Calape', 'latitude' => 9.89, 'longitude' => 123.88,
        ]);
        $this->otherBarangay = Barangay::create([
            'psgc_code' => '0701210001', 'municipality_id' => $this->otherMunicipality->id, 'name' => 'Desamparados',
        ]);
    }

    /** A small file whose bytes really are a PDF. */
    protected function pdf(string $name = 'report.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n",
        );
    }

    /**
     * Every field of the form, filled in validly.
     *
     * @return array<string, mixed>
     */
    protected function fullPayload(array $overrides = []): array
    {
        return array_merge([
            'intent' => 'publish',
            'title' => 'Mushroom growing for farmers',
            'description' => 'Hands-on training on oyster mushrooms.',
            'category_id' => $this->category->id,
            'municipality_id' => $this->municipality->id,
            'barangay_ids' => [$this->barangay->id],
            'start_date' => '2026-03-02',
            'end_date' => '2026-03-04',
            'beneficiaries' => 40,
            'sector_ids' => [$this->sector->id],
            'partners' => 'LGU Balilihan',
            'amount_spent' => '1500.00',
            'narrative' => 'We trained 40 farmers.',
            'report_file' => $this->pdf(),
            'report_copy_type' => CopyType::Soft->value,
        ], $overrides);
    }

    /**
     * Only the fields a config list names (plus the report's copy type when the report is included).
     *
     * @param  list<string>  $fields
     * @return array<string, mixed>
     */
    protected function payloadWith(array $fields, string $intent, array $overrides = []): array
    {
        $full = $this->fullPayload();
        $payload = array_intersect_key($full, array_flip($fields));

        if (array_key_exists('report_file', $payload)) {
            $payload['report_copy_type'] = $full['report_copy_type'];
        }

        return array_merge($payload, ['intent' => $intent], $overrides);
    }

    /** A record with every field filled and a report document, in the given state. */
    protected function completeService(Campus $campus, ServiceState $state = ServiceState::Draft, array $overrides = []): Service
    {
        $encoder = User::factory()->campusUploader($campus)->create();

        $service = Service::factory()->for($campus)->create(array_merge([
            'title' => 'Mushroom growing for farmers',
            'category_id' => $this->category->id,
            'municipality_id' => $this->municipality->id,
            'start_date' => '2026-03-02',
            'end_date' => '2026-03-04',
            'beneficiaries' => 40,
            'narrative' => 'We trained 40 farmers.',
            'state' => $state,
            'encoded_by' => $encoder->id,
            'published_at' => $state === ServiceState::Published ? now() : null,
        ], $overrides));

        $service->barangays()->attach($this->barangay->id);
        $service->sectors()->attach($this->sector->id);

        Document::factory()->for($service)->create([
            'kind' => DocumentKind::AccomplishmentReport,
            'path' => "documents/{$service->id}/existing-report.pdf",
            'uploaded_by' => $encoder->id,
        ]);

        return $service;
    }
}
