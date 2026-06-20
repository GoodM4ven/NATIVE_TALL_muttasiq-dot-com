<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            [
                'username' => config('app.custom.user.username'),
            ],
            [
                'name' => config('app.custom.user.name'),
                'email' => config('app.custom.user.email'),
                'password' => config('app.custom.user.password'),
            ]
        );
    }
}
