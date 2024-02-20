<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        DB::table('users')->insert([
            'name' => 'FOO_1', // Replace with the desired name
            'email' => 'foo1@foo.com', // Replace with the desired email
            'email_verified_at' => now(),
            'password' => Hash::make('foo-bar-baz'), // Set the desired password
        ]);
        DB::table('users')->insert([
            'name' => 'BAR_1', // Replace with the desired name
            'email' => 'bar1@bar.com', // Replace with the desired email
            'email_verified_at' => now(),
            'password' => Hash::make('foo-bar-baz'), // Set the desired password
        ]);
        DB::table('users')->insert([
            'name' => 'BAZ_1', // Replace with the desired name
            'email' => 'baz1@baz.com', // Replace with the desired email
            'email_verified_at' => now(),
            'password' => Hash::make('foo-bar-baz'), // Set the desired password
        ]);
    }
}
