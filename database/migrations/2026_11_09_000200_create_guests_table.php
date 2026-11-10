<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('guest_categories')->nullOnDelete();
            $table->string('name', 150);
            $table->string('family_name', 150)->nullable();
            $table->string('name_normalized', 150);
            $table->string('phone', 30)->nullable();
            $table->string('phone_normalized', 20)->nullable();
            $table->string('email', 190)->nullable();
            $table->text('address')->nullable();
            $table->string('organization', 150)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('quota')->default(1);
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['event_id', 'name']);
            $table->index(['event_id', 'family_name']);
            $table->index(['event_id', 'phone_normalized']);
            $table->index(['event_id', 'name_normalized']);
            $table->index(['event_id', 'category_id']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE guests ADD CONSTRAINT guests_quota_check CHECK (quota BETWEEN 1 AND 100)');
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
            DB::statement('CREATE INDEX guests_name_trgm ON guests USING gin (lower(name) gin_trgm_ops)');
            DB::statement('CREATE INDEX guests_family_trgm ON guests USING gin (lower(family_name) gin_trgm_ops)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
