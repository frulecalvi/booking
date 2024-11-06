<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $operatorUser = User::factory()->create([
            'email' => 'operatortest@test.com',
            'password' => 'test'
        ]);
        $operatorUser->assignRole('Operator');

        $adminUser = User::factory()->create([
            'email' => 'admintest@test.com',
            'password' => 'test'
        ]);
        $adminUser->assignRole('Admin');
    }
}
