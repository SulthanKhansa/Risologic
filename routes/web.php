<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// TEMPORARY ROUTE TO RUN SEEDER
Route::get('/setup-users', function () {
    try {
        // Run shield generate just in case
        \Illuminate\Support\Facades\Artisan::call('shield:generate', ['--all' => true]);

        // Force delete if they exist to start fresh
        \Illuminate\Support\Facades\DB::table('users')->whereIn('username', ['admin', 'staff'])->delete();

        // Create Admin directly via DB to bypass auto-hashing just in case
        \Illuminate\Support\Facades\DB::table('users')->insert([
            'username' => 'admin',
            'name' => 'Administrator',
            'email' => 'admin@risologic.com',
            'password' => \Illuminate\Support\Facades\Hash::make('110402'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create Staff directly via DB
        \Illuminate\Support\Facades\DB::table('users')->insert([
            'username' => 'staff',
            'name' => 'Staff Karyawan',
            'email' => 'staff@risologic.com',
            'password' => \Illuminate\Support\Facades\Hash::make('12345'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assign Roles (Eloquent is fine here since record now exists in DB)
        $admin = \App\Models\User::where('username', 'admin')->first();
        $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => config('filament-shield.super_admin.name', 'super_admin'), 'guard_name' => 'web']);
        $admin->assignRole($adminRole);

        $staff = \App\Models\User::where('username', 'staff')->first();
        $staffRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $staffPermissions = \Spatie\Permission\Models\Permission::where('name', 'like', 'view_%')
            ->orWhere('name', 'like', 'widget_%')->get();
        $staffRole->syncPermissions($staffPermissions);
        $staff->assignRole($staffRole);

        return 'BERHASIL TOTAL! Akun sudah di-reset ulang paksa. <br> 
                Login Admin: <b>admin</b> / <b>110402</b><br>
                Login Staff: <b>staff</b> / <b>12345</b>';
    } catch (\Exception $e) {
        return 'Gagal: ' . $e->getMessage();
    }
});
