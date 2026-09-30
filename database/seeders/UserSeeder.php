<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UserSeeder extends Seeder
{
    /**
     * Create the admin accounts, the player and the player's empty game.
     */
    public function run(): void
    {
        $password = config('app.seed_users_password');

        if (blank($password)) {
            throw new RuntimeException('Set SEED_USERS_PASSWORD in .env before seeding users.');
        }

        $password = Hash::make($password);

        $users = [
            ['email' => 't.lugowski@fortis.pl', 'name' => 'T. Ługowski', 'is_admin' => true],
            ['email' => 'm.piorko@fortis.pl', 'name' => 'M. Piórko', 'is_admin' => true],
            ['email' => 'w.mazur@fortis.pl', 'name' => 'W. Mazur', 'is_admin' => true],
            ['email' => 'm.kobylinski@fortis.pl', 'name' => 'M. Kobyliński', 'is_admin' => false],
        ];

        foreach ($users as $user) {
            $user = User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'is_admin' => $user['is_admin'],
                    'password' => $password,
                    'email_verified_at' => now(),
                ],
            );

            if (! $user->is_admin) {
                Game::firstOrCreate(['user_id' => $user->id]);
            }
        }
    }
}
