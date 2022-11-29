<?php

use App\Club;
use Illuminate\Database\Seeder;

class ClubSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Club::firstOrCreate([
            'club_name' => 'Club del futuro',
            'base_color' => '#00b1ff'
        ]);

        Club::firstOrCreate([
            'club_name' => 'Club verde',
            'base_color' => '#38761d'
        ]);

        Club::firstOrCreate([
            'club_name' => 'Club de arte',
            'base_color' => '#de3186'
        ]);

        Club::firstOrCreate([
            'club_name' => 'Club de arte',
            'base_color' => '#de3186'
        ]);

        Club::firstOrCreate([
            'club_name' => 'Club de felicidad',
            'base_color' => '#f49c17'
        ]);

        Club::firstOrCreate([
            'club_name' => 'Club de letras',
            'base_color' => '#7842a0'
        ]);
    }
}
