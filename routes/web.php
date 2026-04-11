<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// TEMPORARY ROUTE TO RUN SEEDER
Route::get('/setup-users', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('shield:generate', ['--all' => true]);

        $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => config('filament-shield.super_admin.name', 'super_admin'), 'guard_name' => 'web']);
        $admin = \App\Models\User::firstOrCreate(
            ['username' => 'admin'],
            ['name' => 'Administrator', 'email' => 'admin@risologic.com', 'password' => \Illuminate\Support\Facades\Hash::make('110402')]
        );
        $admin->update(['password' => \Illuminate\Support\Facades\Hash::make('110402')]);
        $admin->assignRole($adminRole);

        $staffRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $staffPermissions = \Spatie\Permission\Models\Permission::where('name', 'like', 'view_%')
            ->orWhere('name', 'like', 'widget_%')->get();
        $staffRole->syncPermissions($staffPermissions);

        $staff = \App\Models\User::firstOrCreate(
            ['username' => 'staff'],
            ['name' => 'Staff Karyawan', 'email' => 'staff@risologic.com', 'password' => \Illuminate\Support\Facades\Hash::make('12345')]
        );
        $staff->update(['password' => \Illuminate\Support\Facades\Hash::make('12345')]);
        $staff->assignRole($staffRole);

        return 'Sukses! Berhasil memuat ulang hak akses dan password. Silakan login (admin / 110402 atau staff / 12345).';
    } catch (\Exception $e) {
        return 'Gagal: ' . $e->getMessage();
    }
});
