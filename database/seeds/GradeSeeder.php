<?php

use App\Grade;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Grade::create([
            'grade_number' => 1,
            'grade_name' => 'PRIMER GRADO',
            'school_level' => 'PREESCOLAR',
        ]);

        Grade::create([
            'grade_number' => 2,
            'grade_name' => 'SEGUNDO GRADO',
            'school_level' => 'PREESCOLAR',
        ]);

        Grade::create([
            'grade_number' => 3,
            'grade_name' => 'TERCER GRADO',
            'school_level' => 'PREESCOLAR',
        ]);

        Grade::create([
            'grade_number' => 1,
            'grade_name' => 'PRIMER GRADO',
            'school_level' => 'PRIMARIA',
        ]);

        Grade::create([
            'grade_number' => 2,
            'grade_name' => 'SEGUNDO GRADO',
            'school_level' => 'PRIMARIA',
        ]);

        Grade::create([
            'grade_number' => 3,
            'grade_name' => 'TERCER GRADO',
            'school_level' => 'PRIMARIA',
        ]);

        Grade::create([
            'grade_number' => 4,
            'grade_name' => 'CUARTO GRADO',
            'school_level' => 'PRIMARIA',
        ]);

        Grade::create([
            'grade_number' => 5,
            'grade_name' => 'QUINTO GRADO',
            'school_level' => 'PRIMARIA',
        ]);

        Grade::create([
            'grade_number' => 6,
            'grade_name' => 'SEXTO GRADO',
            'school_level' => 'PRIMARIA',
        ]);
    }
}
