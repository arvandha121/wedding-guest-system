<?php

use App\Enums\SessionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_sessions', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->timestampTz('start_time');
            $table->timestampTz('end_time');
            $table->timestampTz('checkin_opens_at')->nullable();
            $table->enum('status', SessionStatus::values())->default(SessionStatus::Scheduled->value);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestampsTz();

            $table->index(['event_id', 'start_time']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE event_sessions ADD CONSTRAINT event_sessions_time_check CHECK (end_time > start_time)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_sessions');
    }
};
