<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\GameTest;

class GameTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        GameTest::create(['name' => 'The Legend of Zelda', 'cost' => 60]);
        GameTest::create(['name' => 'Super Mario Galaxy', 'cost' => 50]);
        GameTest::create(['name' => 'Mario Kart 8', 'cost' => 45]);
        GameTest::create(['name' => 'Minecraft', 'cost' => 30]);
    }
}
