<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// TEMPORARY ROUTE TO RUN REPAIR & DIAGNOSTICS
Route::get('/setup-users', function () {
    try {
        $results = [];

        // 1. Check SQLite
        $results['db_connection'] = \Illuminate\Support\Facades\DB::connection()->getDatabaseName();
        
        // 2. Run migrations
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $results['migration_output'] = \Illuminate\Support\Facades\Artisan::output();

        // 3. Check table columns
        $results['sales_columns'] = \Illuminate\Support\Facades\Schema::getColumnListing('sales');
        $results['users_columns'] = \Illuminate\Support\Facades\Schema::getColumnListing('users');

        // 4. Force Reset Users
        \Illuminate\Support\Facades\DB::table('users')->whereIn('username', ['admin', 'staff'])->delete();
        \Illuminate\Support\Facades\DB::table('users')->insert([
            'username' => 'admin',
            'name' => 'Administrator',
            'email' => 'admin@risologic.com',
            'password' => \Illuminate\Support\Facades\Hash::make('110402'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 5. Check Extensions
        $results['intl_enabled'] = extension_loaded('intl');

        // 6. Get Last Log Errors (if any)
        $logPath = storage_path('logs/laravel.log');
        $results['last_log'] = file_exists($logPath) ? substr(file_get_contents($logPath), -2000) : 'No log file found';

        return response()->json($results);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    }
});
