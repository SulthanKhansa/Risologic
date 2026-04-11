<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// TEMPORARY ROUTE TO RUN SEEDER
Route::get('/setup-users', function () {
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'Database\Seeders\RoleAndUserSeeder']);
    return 'Users and Roles setup complete! (Admin: admin/110402, Staff: staff/12345)';
});
