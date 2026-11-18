<?php

namespace Database\Factories;

use App\Enums\CopyType;
use App\Enums\DocumentKind;
use App\Models\Document;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'kind' => DocumentKind::AccomplishmentReport,
            'copy_type' => CopyType::Soft,
            'original_name' => 'report.pdf',
            'path' => 'documents/report.pdf',
            'mime' => 'application/pdf',
            'size' => 1024,
            'uploaded_by' => User::factory(),
        ];
    }
}
