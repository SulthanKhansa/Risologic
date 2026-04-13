<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// TEMPORARY ROUTE TO RUN REPAIR & DIAGNOSTICS
Route::get('/setup-users', function () {
    try {
        $results = [];

        // 1. Database Connection check
        $results['db_connection'] = \Illuminate\Support\Facades\DB::connection()->getDatabaseName();
        
        // 2. Clear All Caches & Optimize
        \Illuminate\Support\Facades\Artisan::call('optimize:clear');
        $results['cache_clear'] = \Illuminate\Support\Facades\Artisan::output();

        // 3. Run migrations (including the new performance indexes)
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $results['migration_output'] = \Illuminate\Support\Facades\Artisan::output();

        // 4. Force Reset Users & Roles
        \Illuminate\Support\Facades\DB::table('users')->whereIn('username', ['admin', 'staff'])->delete();
        $admin = \App\Models\User::create([
            'username' => 'admin',
            'name' => 'Administrator',
            'email' => 'admin@risologic.com',
            'password' => \Illuminate\Support\Facades\Hash::make('110402'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 5. Assign Super Admin Role
        try {
            \Illuminate\Support\Facades\Artisan::call('shield:install', ['--all' => true, '--no-interaction' => true]);
            $admin->assignRole('super_admin');
            $results['role_assignment'] = 'Success: super_admin assigned';
        } catch (\Exception $e) {
            $results['role_assignment'] = 'Skipped: ' . $e->getMessage();
        }

        // 6. Check System Environment
        $results['intl_enabled'] = extension_loaded('intl');
        $results['php_version'] = PHP_VERSION;

        // 7. Get Last Log Errors (truncated to 5000 chars)
        $logPath = storage_path('logs/laravel.log');
        $results['last_log'] = file_exists($logPath) ? substr(file_get_contents($logPath), -5000) : 'No log file found';

        return response()->json($results);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    }
});
