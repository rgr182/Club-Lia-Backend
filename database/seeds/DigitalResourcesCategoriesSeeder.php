<?php

use App\DigitalResourcesCategories;
use Illuminate\Database\Seeder;

class DigitalResourcesCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DigitalResourcesCategories::firstOrCreate([
            'id'  => 1,
            'name'  => 'Video'
        ]);

        DigitalResourcesCategories::firstOrCreate([
            'id'  => 2,
            'name'  => 'Canción'
        ]);

        DigitalResourcesCategories::firstOrCreate([
            'id'  => 3,
            'name'  => 'Audio'
        ]);

        DigitalResourcesCategories::firstOrCreate([
            'id'  => 4,
            'name'  => 'Lectura'
        ]);

        DigitalResourcesCategories::firstOrCreate([
            'id'  => 5,
            'name'  => 'Relacionar imágenes'
        ]);

        DigitalResourcesCategories::firstOrCreate([
            'id'  => 6,
            'name'  => 'Relacionar columnas'
        ]);

        DigitalResourcesCategories::firstOrCreate([
            'id'  => 7,
            'name'  => 'Adivinanzas con imagen'
        ]);

        DigitalResourcesCategories::firstOrCreate([
            'id'  => 8,
            'name'  => 'Completar la frase'
        ]);

        DigitalResourcesCategories::firstOrCreate([
            'id'  => 9,
            'name'  => 'Quiz'
        ]);

        DigitalResourcesCategories::firstOrCreate([
            'id'  => 10,
            'name'  => 'Rompecabezas'
        ]);

        DigitalResourcesCategories::firstOrCreate([
            'id'  => 11,
            'name'  => 'Presentación'
        ]);

        DigitalResourcesCategories::firstOrCreate([
            'id'  => 12,
            'name'  => 'Secuencia'
        ]);

        DigitalResourcesCategories::firstOrCreate([
            'id'  => 13,
            'name'  => 'Crucigrama'
        ]);

        DigitalResourcesCategories::firstOrCreate([
            'id'  => 14,
            'name'  => 'Memorama'
        ]);

        DigitalResourcesCategories::firstOrCreate([
            'id'  => 15,
            'name'  => 'Colorear'
        ]);

        DigitalResourcesCategories::firstOrCreate([
            'id'  => 16,
            'name'  => 'Sopa de letras'
        ]);

        DigitalResourcesCategories::firstOrCreate([
            'id'  => 17,
            'name'  => 'Escape Room'
        ]);

        DigitalResourcesCategories::firstOrCreate([
            'id'  => 18,
            'name'  => 'Cuestionario'
        ]);

        DigitalResourcesCategories::firstOrCreate([
            'id'  => 19,
            'name'  => 'Receta'
        ]);

        DigitalResourcesCategories::firstOrCreate([
            'id'  => 20,
            'name'  => 'Proyecto'
        ]);

        DigitalResourcesCategories::firstOrCreate([
            'id'  => 21,
            'name'  => 'Experimento'
        ]);

        DigitalResourcesCategories::firstOrCreate([
            'id'  => 22,
            'name'  => 'Juego de mesa'
        ]);

        DigitalResourcesCategories::firstOrCreate([
            'id'  => 23,
            'name'  => 'VideoJuego'
        ]);

        DigitalResourcesCategories::firstOrCreate([
            'id'  => 24,
            'name'  => 'Reto'
        ]);

        DigitalResourcesCategories::firstOrCreate([
            'id'  => 25,
            'name'  => 'Ruleta'
        ]);

        DigitalResourcesCategories::firstOrCreate([
            'id'  => 26,
            'name'  => 'Evaluación'
        ]);

        DigitalResourcesCategories::firstOrCreate([
            'id'  => 27,
            'name'  => 'Otro'
        ]);
    }
}