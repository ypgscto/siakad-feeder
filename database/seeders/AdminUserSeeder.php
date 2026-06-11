<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('SIFEEDER_SEED_ADMIN_EMAIL', 'admin@gmail.com');
        $password = (string) env('SIFEEDER_SEED_ADMIN_PASSWORD', '123456');
        $login = (string) env('SIFEEDER_SEED_ADMIN_LOGIN', $email);

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrator Siakad-Feeder',
                'password' => Hash::make($password),
                'siakad_user_id' => null,
                'siakad_login' => null,
                'jenis_user' => '9',
                'role' => 'superadmin',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
    }
}
