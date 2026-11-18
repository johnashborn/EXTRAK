<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Default exists only because SQLite needs one for a new NOT NULL column.
            $table->string('role', 30)->default('campus_director');
            $table->foreignId('campus_id')->nullable()->constrained('campuses');
            $table->boolean('is_active')->default(true);
            $table->string('auth_provider', 20)->default('local');
            $table->string('external_id')->nullable()->unique();
            $table->dateTime('last_login_at')->nullable();
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('campus_id');
            $table->dropUnique(['external_id']);
            $table->dropColumn(['role', 'is_active', 'auth_provider', 'external_id', 'last_login_at']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
