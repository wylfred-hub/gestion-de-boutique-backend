<?php

// namespace Database\Seeders;

// use App\Models\User;
// use Illuminate\Database\Seeder;
// use Illuminate\Support\Facades\Hash;

// class UserSeeder extends Seeder
// {
//     public function run(): void
//     {
//         if (!User::where('email', 'wylfreddev@gmail.com')->exists()) {
//             User::create([
//                 'name'     => 'Wylfred dev',
//                 'email'    => 'wylfreddev@gmail.com',
//                 'password' => Hash::make('wylfreddev'),
//                 'role'     => 'super_admin',
//                 'is_active' => true,
//             ]);
//         }
//     }
// }


namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'wylfreddev@gmail.com'], // critère de recherche
            [
                'name'      => 'Wylfred dev',
                'password'  => Hash::make('wylfreddev123'),
                'role'      => 'super_admin',
                'is_active' => true,
            ]
        );
    }
}