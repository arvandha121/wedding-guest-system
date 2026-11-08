<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $name = (string) config('wgs.seed_admin.name');
        $email = config('wgs.seed_admin.email');
        $password = config('wgs.seed_admin.password');

        if (blank($email) || blank($password)) {
            throw new RuntimeException(
                'Isi SEED_ADMIN_EMAIL dan SEED_ADMIN_PASSWORD di .env sebelum menjalankan seeder.'
            );
        }

        $user = User::withTrashed()->firstOrNew(['email' => $email]);

        // Kata sandi hanya diatur saat akun BARU dibuat; menjalankan ulang seeder
        // tidak menimpa kata sandi yang sudah diganti.
        if (! $user->exists) {
            $user->password = $password;
        }

        $user->name = $name;
        $user->role = UserRole::SuperAdmin;
        $user->status = UserStatus::Active;
        $user->email_verified_at ??= now();
        $user->deleted_at = null;
        $user->save();
    }
}
