<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

 class ResponsableSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'nom' => 'Ro',
            'email' => 'ro@example.com',
            'mot_de_passe' => Hash::make('01010101'),
            'role' => 'responsable',
        ]);
    }
}
