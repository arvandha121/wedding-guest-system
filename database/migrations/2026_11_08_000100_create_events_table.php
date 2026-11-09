<?php

use App\Enums\CheckinMode;
use App\Enums\EventStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->string('name', 150);
            $table->string('title', 200);
            $table->string('groom_name', 100);
            $table->string('bride_name', 100);
            $table->string('groom_family', 150)->nullable();
            $table->string('bride_family', 150)->nullable();
            $table->text('description')->nullable();
            $table->date('event_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('venue_name', 150);
            $table->text('venue_address');
            $table->string('google_maps_url', 500)->nullable();
            $table->string('banner_path')->nullable();
            $table->string('couple_photo_path')->nullable();
            $table->string('theme', 50)->default('classic');
            $table->enum('status', EventStatus::values())->default(EventStatus::Draft->value);
            $table->enum('checkin_mode', CheckinMode::values())->default(CheckinMode::Single->value);
            $table->timestampTz('checkin_opens_at')->nullable();
            $table->timestampTz('checkin_closes_at')->nullable();
            $table->boolean('allow_manual_checkin')->default(true);
            $table->string('contact_name', 100)->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->jsonb('settings')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('status');
            $table->index('event_date');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE events ADD CONSTRAINT events_checkin_window_check '
                . 'CHECK (checkin_opens_at IS NULL OR checkin_closes_at IS NULL OR checkin_closes_at > checkin_opens_at)'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
