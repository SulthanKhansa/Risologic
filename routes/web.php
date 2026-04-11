<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// TEMPORARY ROUTE TO RUN REPAIR
Route::get('/setup-users', function () {
    try {
        // 1. Run migrations to ensure schema is up to date (fixes missing customer_name etc)
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $migrationOutput = \Illuminate\Support\Facades\Artisan::output();

        // 2. Force delete if they exist to start fresh
        \Illuminate\Support\Facades\DB::table('users')->whereIn('username', ['admin', 'staff'])->delete();

        // 3. Create Admin directly via DB
        \Illuminate\Support\Facades\DB::table('users')->insert([
            'username' => 'admin',
            'name' => 'Administrator',
            'email' => 'admin@risologic.com',
            'password' => \Illuminate\Support\Facades\Hash::make('110402'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Create Staff directly via DB
        \Illuminate\Support\Facades\DB::table('users')->insert([
            'username' => 'staff',
            'name' => 'Staff Karyawan',
            'email' => 'staff@risologic.com',
            'password' => \Illuminate\Support\Facades\Hash::make('12345'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 5. Assign Roles
        $admin = \App\Models\User::where('username', 'admin')->first();
        $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => config('filament-shield.super_admin.name', 'super_admin'), 'guard_name' => 'web']);
        $admin->assignRole($adminRole);

        $staff = \App\Models\User::where('username', 'staff')->first();
        $staffRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $staffPermissions = \Spatie\Permission\Models\Permission::where('name', 'like', 'view_%')
            ->orWhere('name', 'like', 'widget_%')->get();
        $staffRole->syncPermissions($staffPermissions);
        $staff->assignRole($staffRole);

        return 'BERHASIL REPARASI! <br><br>' . 
                '<b>Hasil Migrasi:</b><br><pre>' . $migrationOutput . '</pre><br>' .
                'Akun sudah di-reset ulang paksa.<br> 
                Login Admin: <b>admin</b> / <b>110402</b><br>
                Login Staff: <b>staff</b> / <b>12345</b>';
    } catch (\Exception $e) {
        return 'Gagal Reparasi: ' . $e->getMessage();
    }
});
