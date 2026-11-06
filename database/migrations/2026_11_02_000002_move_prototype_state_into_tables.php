<?php

use App\Services\Prototype\DashboardDataStore;
use Database\Seeders\ExtensionReferenceSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moves the dashboards' data out of the single JSON document in prototype_states
 * and into the extension tables, then drops prototype_states.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new ExtensionReferenceSeeder)->run();

        if (! Schema::hasTable('prototype_states')) {
            return;
        }
        $saved = DB::table('prototype_states')->where('key', 'default')->first();
        if ($saved) {
            $store = app(DashboardDataStore::class);
            $store->import(json_decode($saved->data, true) ?: [], null);
            // keep counting from the old version, so dashboards left open pick up the change
            \App\Models\Setting::put('data_version', max($store->version(), (int) $saved->version + 1));
        }
        Schema::drop('prototype_states');
    }

    public function down(): void
    {
        Schema::create('prototype_states', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->longText('data');
            $table->unsignedInteger('version')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $store = app(DashboardDataStore::class);
        $data = $store->export();
        if ($data !== null) {
            DB::table('prototype_states')->insert([
                'key' => 'default', 'data' => json_encode($data), 'version' => $store->version(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }
};
