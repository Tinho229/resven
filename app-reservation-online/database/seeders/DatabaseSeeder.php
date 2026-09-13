<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();


        User::create([
            'nom' => 'Tinho',
            'email' => 'tinho@example.com',
            'mot_de_passe' => Hash::make('01010101'),
            'role' => 'admin',
        ]);


    }
}
