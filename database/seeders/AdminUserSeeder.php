<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            User::create([
                'name' => 'Admin',
                'email' => 'admin@starter.loc',
                'password' => bcrypt('password'),
            ]);
            echo "Admin user created successfully.\n";
        } catch(\Exception $e) {
            echo "Admin user already exists.\n";
        }
    }
}
