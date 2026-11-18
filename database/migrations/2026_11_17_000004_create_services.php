<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A completed extension service (the repository record) and what it links to.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->nullable()->unique();
            $table->foreignId('campus_id')->constrained();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained();
            $table->foreignId('municipality_id')->nullable()->constrained();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedInteger('beneficiaries')->nullable();
            $table->string('partners')->nullable();
            $table->decimal('amount_spent', 12, 2)->nullable();
            $table->text('narrative')->nullable();
            $table->string('state', 20)->default('draft');
            $table->json('reader_suggestions')->nullable();
            $table->foreignId('encoded_by')->constrained('users');
            $table->foreignId('published_by')->nullable()->constrained('users');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->string('delete_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['campus_id', 'state']);
        });

        Schema::create('service_barangay', function (Blueprint $table) {
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('barangay_id')->constrained()->cascadeOnDelete();
            $table->primary(['service_id', 'barangay_id']);
        });

        Schema::create('service_sector', function (Blueprint $table) {
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('beneficiary_sector_id')->constrained()->cascadeOnDelete();
            $table->primary(['service_id', 'beneficiary_sector_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_sector');
        Schema::dropIfExists('service_barangay');
        Schema::dropIfExists('services');
    }
};
