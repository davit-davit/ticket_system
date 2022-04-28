<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\user;
use Hash;

class UserSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            "name" => "Giorgi Katsarava",
            "email" => "giorgi.katsarava@rda.gov.ge",
            "role" => 1,
            "password" => Hash::make(1234)
        ]);
    }
}
