<?php

use App\Enums\CheckinMode;
use App\Enums\InvitationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->string('invitation_code', 12)->unique();
            $table->char('public_token_hash', 64)->unique();
            $table->text('public_token_enc');
            $table->char('qr_token_hash', 64)->unique();
            $table->text('qr_token_enc');
            $table->unsignedSmallInteger('qr_version')->default(1);
            $table->unsignedSmallInteger('quota');
            $table->enum('checkin_mode', CheckinMode::values())->nullable();
            $table->enum('status', InvitationStatus::values())->default(InvitationStatus::Generated->value);
            $table->timestampTz('generated_at')->nullable();
            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('delivered_at')->nullable();
            $table->timestampTz('opened_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('revoke_reason')->nullable();
            $table->timestampsTz();

            $table->index(['event_id', 'status']);
        });

        Schema::create('invitation_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_session_id')->constrained('event_sessions')->restrictOnDelete();
            $table->unsignedSmallInteger('attended_count')->default(0);
            $table->timestampsTz();

            $table->unique(['invitation_id', 'event_session_id']);
        });

        Schema::create('invitation_delivery_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->enum('channel', ['email', 'whatsapp']);
            $table->string('recipient');
            $table->string('provider', 50);
            $table->string('provider_message_id', 120)->nullable()->index();
            $table->enum('status', ['queued', 'sent', 'delivered', 'read', 'failed'])->default('queued');
            $table->unsignedTinyInteger('attempt')->default(0);
            $table->text('error_message')->nullable();
            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('delivered_at')->nullable();
            $table->timestampTz('read_at')->nullable();
            $table->timestampTz('failed_at')->nullable();
            $table->timestampsTz();
        });

        if (DB::getDriverName() === 'pgsql') {
            // Satu undangan aktif per tamu; undangan dicabut tidak dihitung.
            DB::statement("CREATE UNIQUE INDEX invitations_active_guest_unique ON invitations (guest_id) WHERE status <> 'revoked'");
            DB::statement('ALTER TABLE invitations ADD CONSTRAINT invitations_quota_check CHECK (quota BETWEEN 1 AND 100)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_delivery_logs');
        Schema::dropIfExists('invitation_sessions');
        Schema::dropIfExists('invitations');
    }
};
