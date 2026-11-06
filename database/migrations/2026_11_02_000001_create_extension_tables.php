<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The extension office data, one table per thing (docs/spec.md section 10).
 * The people in the workflow are kept as officials, separate from the login accounts in users.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('municipality', 100);
            $table->timestamps();
        });

        Schema::create('officials', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique(); // how the dashboards refer to them, e.g. ed, cd, Main-cd
            $table->string('name');
            $table->string('title');
            $table->string('role', 30); // extension_director | extension_assistant | campus_director | campus_assistant | president
            $table->foreignId('campus_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assistant_of')->nullable()->constrained('officials')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        Schema::create('beneficiary_sectors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->boolean('is_custom')->default(false); // added by the extension office
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('barangays', function (Blueprint $table) {
            $table->id();
            $table->string('municipality', 100);
            $table->string('name', 100);
            $table->timestamps();
            $table->unique(['municipality', 'name']);
        });

        Schema::create('extension_requests', function (Blueprint $table) {
            $table->id();
            $table->string('ref', 50)->unique(); // the dashboards' id, e.g. r14
            $table->string('code', 30)->unique(); // e.g. BAL-2026-014
            $table->foreignId('campus_id')->constrained();
            $table->foreignId('requested_by')->nullable()->constrained('officials')->nullOnDelete();
            $table->foreignId('encoded_by')->nullable()->constrained('officials')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('municipality', 100);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('proposed_budget', 12, 2)->default(0);
            $table->unsignedInteger('expected_beneficiaries')->default(0);
            $table->string('partners')->nullable();
            $table->string('status', 20)->index();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('request_barangay', function (Blueprint $table) {
            $table->foreignId('extension_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('barangay_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->primary(['extension_request_id', 'barangay_id']);
        });

        Schema::create('request_sector', function (Blueprint $table) {
            $table->foreignId('extension_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('beneficiary_sector_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->primary(['extension_request_id', 'beneficiary_sector_id']);
        });

        Schema::create('request_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extension_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('official_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->text('remark')->nullable();
            $table->dateTime('happened_at');
            $table->unsignedInteger('position')->default(0);
        });

        Schema::create('request_remarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extension_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('official_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->dateTime('made_at');
            $table->unsignedInteger('position')->default(0);
        });

        Schema::create('overlap_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extension_request_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->string('match_code', 30)->nullable();
            $table->json('match'); // what the similar work looked like when it was flagged
            $table->json('terms');
            $table->string('resolution', 20)->nullable(); // allow | coordinate
            $table->foreignId('resolved_by')->nullable()->constrained('officials')->nullOnDelete();
        });

        Schema::create('accomplishment_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extension_request_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('actual_start')->nullable();
            $table->date('actual_end')->nullable();
            $table->decimal('actual_budget', 12, 2)->default(0);
            $table->unsignedInteger('actual_beneficiaries')->default(0);
            $table->text('narrative')->nullable();
            $table->foreignId('encoded_by')->nullable()->constrained('officials')->nullOnDelete();
            $table->timestamps();
        });

        // supporting documents of a request, and photos of an accomplishment report
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable');
            $table->string('ref', 60)->nullable();
            $table->string('kind', 20); // letter | proposal | budget | moa | other | photo
            $table->foreignUuid('file_id')->nullable()->constrained('prototype_files')->nullOnDelete();
            $table->string('name');
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->mediumText('url'); // the file's address; sample data keeps a generated picture here
            $table->boolean('is_sample')->default(false);
            $table->foreignId('uploaded_by')->nullable()->constrained('officials')->nullOnDelete();
            $table->dateTime('uploaded_at')->nullable();
            $table->unsignedInteger('position')->default(0);
        });

        Schema::create('dashboard_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('ref', 60)->unique();
            $table->string('recipient', 20)->index(); // ed | cd | pres
            $table->string('kind', 20);
            $table->text('text');
            $table->foreignId('extension_request_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('request_ref', 50)->nullable(); // kept when the request is not saved yet
            $table->boolean('is_read')->default(false);
            $table->dateTime('sent_at');
            $table->unsignedInteger('position')->default(0);
        });

        Schema::create('university_budgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->decimal('amount', 14, 2);
            $table->timestamps();
        });

        Schema::create('campus_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->decimal('allocation', 14, 2);
            $table->timestamps();
            $table->unique(['campus_id', 'year']);
        });

        Schema::create('budget_changes', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->foreignId('campus_id')->nullable()->constrained()->cascadeOnDelete(); // null: the university total
            $table->string('kind', 10); // set | topup
            $table->decimal('from_amount', 14, 2);
            $table->decimal('to_amount', 14, 2);
            $table->text('reason')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('officials')->nullOnDelete();
            $table->dateTime('changed_at');
            $table->unsignedInteger('position')->default(0);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 50)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'settings', 'budget_changes', 'campus_budgets', 'university_budgets', 'dashboard_notifications',
            'attachments', 'accomplishment_reports', 'overlap_flags', 'request_remarks', 'request_events',
            'request_sector', 'request_barangay', 'extension_requests', 'barangays', 'beneficiary_sectors',
            'categories', 'officials', 'campuses',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
