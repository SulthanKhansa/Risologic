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
            // First check if role exists, if not generate it
            if (\DB::table('roles')->where('name', 'super_admin')->doesntExist()) {
                \Illuminate\Support\Facades\Artisan::call('shield:generate', ['--all' => true]);
            }
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

// TEMPORARY: Deep login diagnostics
Route::get('/debug-login', function () {
    try {
        $results = [];

        // 1. Check if user exists
        $user = \App\Models\User::where('username', 'admin')->first();
        $results['user_found'] = $user ? true : false;
        $results['user_id'] = $user?->id;
        $results['user_email'] = $user?->email;
        $results['user_username'] = $user?->username;

        // 2. Check password
        if ($user) {
            $results['password_valid'] = \Illuminate\Support\Facades\Hash::check('110402', $user->password);
            $results['password_hash_prefix'] = substr($user->password, 0, 20) . '...';
        }

        // 3. Test Auth::attempt directly
        $results['auth_attempt'] = \Illuminate\Support\Facades\Auth::attempt([
            'username' => 'admin',
            'password' => '110402',
        ]);
        \Illuminate\Support\Facades\Auth::logout();

        // 4. Check session driver & config
        $results['session_driver'] = config('session.driver');
        $results['session_connection'] = config('session.connection');
        $results['session_table'] = config('session.table');
        $results['session_domain'] = config('session.domain');
        $results['session_secure'] = config('session.secure');
        $results['session_same_site'] = config('session.same_site');

        // 5. Check if sessions table exists
        $results['sessions_table_exists'] = \Illuminate\Support\Facades\Schema::hasTable('sessions');

        // 6. Check APP_URL vs actual URL
        $results['app_url'] = config('app.url');
        $results['app_env'] = config('app.env');
        $results['app_debug'] = config('app.debug');

        // 7. Count sessions in DB
        if ($results['sessions_table_exists']) {
            $results['session_count'] = \Illuminate\Support\Facades\DB::table('sessions')->count();
        }

        // 8. Check FilamentShield - roles
        $results['user_roles'] = $user ? $user->getRoleNames()->toArray() : [];

        // 9. canAccessPanel check
        if ($user) {
            $results['can_access_panel'] = $user->canAccessPanel(\Filament\Facades\Filament::getDefaultPanel());
        }

        return response()->json($results);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    }
});
