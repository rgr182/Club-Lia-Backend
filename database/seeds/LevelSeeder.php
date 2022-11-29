<?php

use App\Level;
use Illuminate\Database\Seeder;

class LevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Level::updateOrCreate([
            'level' => 'preescolar'
        ]);
        Level::updateOrCreate([
            'level' => 'primaria'
        ]);
        Level::updateOrCreate([
            'level' => 'secundaria'
        ]);
        Level::updateOrCreate([
            'level' => 'otro'
        ]);
    }
}