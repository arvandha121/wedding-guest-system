<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Melengkapi tabel `users` bawaan Laravel (tidak mengubah migration bawaan).
| Dijalankan pada database baru; kolom ulid tidak dibuat untuk data lama.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->char('ulid', 26)->unique()->after('id');
            // enum() di PostgreSQL = varchar + CHECK constraint
            $table->enum('role', UserRole::values())->default(UserRole::Receptionist->value)->after('password');
            $table->enum('status', UserStatus::values())->default(UserStatus::Active->value)->after('role');
            $table->boolean('can_correct_checkin')->default(false)->after('status');
            $table->timestampTz('last_login_at')->nullable()->after('can_correct_checkin');
            $table->softDeletes();
            $table->index(['role', 'status']);
        });

        if (DB::getDriverName() === 'pgsql') {
            // Email unik tanpa membedakan huruf besar/kecil.
            DB::statement('CREATE UNIQUE INDEX users_email_lower_unique ON users (lower(email))');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS users_email_lower_unique');
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['role', 'status']);
            $table->dropSoftDeletes();
            $table->dropColumn(['ulid', 'role', 'status', 'can_correct_checkin', 'last_login_at']);
        });
    }
};
