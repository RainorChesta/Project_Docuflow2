<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(DocumentTypeSeeder::class);

        // User::factory(10)->create();

        User::firstOrCreate(
            ['email' => 'admin@dokuflow.id'],
            [
                'name' => 'Administrator',
                'password' => bcrypt('Admin123!'),
                'system_role' => 'admin',
                'is_active' => true,
            ]
        );
    }
}
