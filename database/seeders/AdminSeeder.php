<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'username' => 'admin',
                'email' => 'admin@risologic.com',
                'password' => Hash::make('123'),
            ]
        );

        // Assign super_admin role if Spatie roles exist
        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            $user->assignRole('super_admin');
        }
    }
}
