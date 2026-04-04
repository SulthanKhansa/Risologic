<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class RisolDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample products
        Product::create([
            'name' => 'Risol Mayo',
            'slug' => 'risol-mayo',
            'base_price' => 15000,
            'current_stock' => 200,
        ]);

        Product::create([
            'name' => 'Risol Keju',
            'slug' => 'risol-keju',
            'base_price' => 18000,
            'current_stock' => 150,
        ]);

        Product::create([
            'name' => 'Risol Daging',
            'slug' => 'risol-daging',
            'base_price' => 20000,
            'current_stock' => 180,
        ]);

        Product::create([
            'name' => 'Risol Tahu',
            'slug' => 'risol-tahu',
            'base_price' => 12000,
            'current_stock' => 250,
        ]);

        // Create admin user
        User::create([
            'name' => 'Admin Risol',
            'email' => 'admin@risol.test',
            'password' => bcrypt('password'),
        ]);

        // Create staff user
        User::create([
            'name' => 'Staff Stand',
            'email' => 'staff@risol.test',
            'password' => bcrypt('password'),
        ]);

        $this->command->info('Risol data seeded successfully!');
    }
}
