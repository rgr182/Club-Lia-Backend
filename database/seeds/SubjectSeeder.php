<?php

use App\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Subject::firstOrCreate([
            'name' => 'Habilidades digitales',
            'club_id' => 1,
            'base_color' => '#b6cddb'
        ]);

        Subject::firstOrCreate([
            'name' => 'Matemáticas',
            'club_id' => 1,
            'base_color' => '#8fb4d1'
        ]);

        Subject::firstOrCreate([
            'name' => 'STEAM',
            'club_id' => 1,
            'base_color' => '#6795bd'
        ]);

        Subject::firstOrCreate([
            'name' => 'Tecnología',
            'club_id' => 1,
            'base_color' => '#3c61a7'
        ]);

        Subject::firstOrCreate([
            'name' => 'Ciencias naturales',
            'club_id' => 2,
            'base_color' => '#a5ca97'
        ]);

        Subject::firstOrCreate([
            'name' => 'Geografía',
            'club_id' => 2,
            'base_color' => '#4c873e'
        ]);

        Subject::firstOrCreate([
            'name' => 'Historia',
            'club_id' => 3,
            'base_color' => '#e387a7'
        ]);

        Subject::firstOrCreate([
            'name' => 'Arte',
            'club_id' => 3,
            'base_color' => '#db5995'
        ]);

        Subject::firstOrCreate([
            'name' => 'Educación Socioemocional',
            'club_id' => 4,
            'base_color' => '#f3ebd9'
        ]);

        Subject::firstOrCreate([
            'name' => 'Mindfulness',
            'club_id' => 4,
            'base_color' => '#ecd5b3'
        ]);

        Subject::firstOrCreate([
            'name' => 'Salud y Bienestar',
            'club_id' => 4,
            'base_color' => '#e9c38b'
        ]);

        Subject::firstOrCreate([
            'name' => 'Civismo',
            'club_id' => 4,
            'base_color' => '#dfa761'
        ]);

        Subject::firstOrCreate([
            'name' => 'Civismo digital',
            'club_id' => 4,
            'base_color' => '#ed8d41'
        ]);

        Subject::firstOrCreate([
            'name' => 'Español',
            'club_id' => 5,
            'base_color' => '#a62d90'
        ]);

        Subject::firstOrCreate([
            'name' => 'Inglés',
            'club_id' => 5,
            'base_color' => '#73377e'
        ]);
    }
}
