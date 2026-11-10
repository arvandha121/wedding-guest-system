<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_categories', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestampsTz();

            $table->unique(['event_id', 'name']);
        });

        // Acara yang sudah ada sebelum phase ini ikut mendapat kategori bawaan.
        $now = now();
        foreach (DB::table('events')->pluck('id') as $eventId) {
            foreach (config('wgs.default_guest_categories') as $name) {
                DB::table('guest_categories')->insert([
                    'ulid' => strtolower((string) Str::ulid()), 'event_id' => $eventId, 'name' => $name,
                    'is_default' => true, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_categories');
    }
};
