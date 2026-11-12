<?php

namespace Database\Seeders;

use App\Models\StaffUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LocalDemoAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = mb_strtolower(trim((string) env('CERBERUS_DEMO_ADMIN_EMAIL')));
        $password = (string) env('CERBERUS_DEMO_ADMIN_PASSWORD');

        if ($email === '' || $password === '' || strlen($password) < 12 || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        StaffUser::query()->updateOrCreate(
            ['email' => $email],
            ['display_name' => 'Local Demo Admin', 'password' => Hash::make($password), 'role' => 'admin', 'active' => true]
        );
    }
}
