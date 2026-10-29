<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prototype only: the dashboards' shared data, saved as one JSON document.
 * The real system replaces this with the tables in docs/spec.md section 10.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prototype_states', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->longText('data');
            $table->unsignedInteger('version')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prototype_states');
    }
};
