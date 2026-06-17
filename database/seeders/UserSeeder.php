<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (!User::where('email', 'superadmin@cachet.com')->exists()) {
            User::create([
                'name'     => 'Wylfred dev',
                'email'    => 'wylfreddev@gmail.com',
                'password' => Hash::make('wylfreddev'),
                'role'     => 'super_admin',
                'is_active' => true,
            ]);
        }
    }
}
