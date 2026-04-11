<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure all permissions are generated first by Filament Shield
        $this->command->info('Generating Filament Shield Permissions...');
        Artisan::call('shield:generate', ['--all' => true]);

        // =======================
        // 1. ADMIN SETUP
        // =======================
        // By default, Filament Shield uses 'super_admin' as the ultimate role
        $adminRole = Role::firstOrCreate(['name' => config('filament-shield.super_admin.name', 'super_admin'), 'guard_name' => 'web']);

        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'email' => 'admin@risologic.com',
                'password' => Hash::make('110402'),
            ]
        );
        
        // If password needs update for existing user:
        $admin->update(['password' => Hash::make('110402')]);
        
        $admin->assignRole($adminRole);
        $this->command->info('Admin user seeded (admin / 110402)');

        // =======================
        // 2. STAFF SETUP
        // =======================
        $staffRole = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        
        // Give staff ONLY view and widget permissions (read-only)
        $staffPermissions = Permission::where('name', 'like', 'view_%')
            ->orWhere('name', 'like', 'widget_%')
            ->get();
            
        $staffRole->syncPermissions($staffPermissions);

        $staff = User::firstOrCreate(
            ['username' => 'staff'],
            [
                'name' => 'Staff Karyawan',
                'email' => 'staff@risologic.com',
                'password' => Hash::make('12345'),
            ]
        );
        
        // If password needs update for existing user:
        $staff->update(['password' => Hash::make('12345')]);
        
        $staff->assignRole($staffRole);
        $this->command->info('Staff user seeded (staff / 12345)');
    }
}
