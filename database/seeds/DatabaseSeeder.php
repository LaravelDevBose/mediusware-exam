<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {

        \App\Models\User::create([
            'name' => 'Arup',
            'email' => 'arup@gm.com',
            'email_verified_at' => now(),
            'password' => \Illuminate\Support\Facades\Hash::make('123456'),
            'remember_token' => Str::random(10),
        ]);
        // $this->call(UserSeeder::class);
    }
}
