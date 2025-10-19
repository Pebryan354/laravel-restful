<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User as UserModel;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        UserModel::create([
            'name' => 'Pebryan Ibrahim',
            'email' => 'pebryan@gmail.com',
            'password' => Hash::make('admin123'),
        ]);
    }
}
