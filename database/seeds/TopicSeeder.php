<?php

use Illuminate\Database\Seeder;
use App\Topic;

class TopicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Topic::firstOrCreate([
            'name' => 'Contenido Académico oficial SEP',
            'id' => 1,
            'slug' => 'contenido_academico',
            'description' => 'Contenido Académico oficial SEP',
            'this_order' => 1,
        ]);

        Topic::firstOrCreate([
            'name' => 'Lenguaje y comunicación: Lectura / Ortografía / gramática',
            'id' => 2,
            'slug' => 'lenguaje_y_comunicacion',
            'description' => 'Lenguaje y comunicación: Lectura / Ortografía / gramática',
            'this_order' => 1,
        ]);

        Topic::firstOrCreate([
            'name' => 'Pensamiento matemático',
            'id' => 3,
            'slug' => 'pensamiento_matematico',
            'description' => 'Pensamiento matemático',
            'this_order' => 1,
        ]);

        Topic::firstOrCreate([
            'name' => 'Ciencias STEM',
            'id' => 4,
            'slug' => 'ciencias_stem',
            'description' => 'Ciencias STEM (Experimentos, retos prácticos de: ciencia Tecnología ingeniería, pensamiento crítico)',
            'this_order' => 1,
        ]);

        Topic::firstOrCreate([
            'name' => 'Tecnología / programación',
            'id' => 5,
            'slug' => 'tecnologia_programacion',
            'description' => 'Tecnología / programación',
            'this_order' => 1,
        ]);

        Topic::firstOrCreate([
            'name' => 'Naturaleza / sociedad ',
            'id' => 6,
            'slug' => 'naturaleza_sociedad ',
            'description' => 'Naturaleza / sociedad ',
            'this_order' => 1,
        ]);

        Topic::firstOrCreate([
            'name' => 'Arte, Creatividad',
            'id' => 7,
            'slug' => 'arte_creatividad',
            'description' => 'Arte, Creatividad',
            'this_order' => 1,
        ]);

        Topic::firstOrCreate([
            'name' => 'Inglés',
            'id' => 8,
            'slug' => 'ingles',
            'description' => 'Inglés',
            'this_order' => 1,
        ]);

        Topic::firstOrCreate([
            'name' => 'Servicios de Interes',
            'id' => 9,
            'slug' => 'servicios_de_i nteres',
            'description' => 'Servicios de Interes (estos son servicios que ofrece LIA para el Alumno o Papa)',
            'this_order' => 1,
        ]);

        Topic::firstOrCreate([
            'name' => 'Contenidos para el grado escolar de mi hijo',
            'id' => 10,
            'slug' => 'contenidos_para_el_grado_escolar_de_mi_hijo',
            'description' => 'Contenidos para el grado escolar de mi hijo',
            'this_order' => 2,
        ]);

        Topic::firstOrCreate([
            'name' => 'Actividades Sugeridas',
            'id' => 11,
            'slug' => 'actividades_sugeridas',
            'description' => 'Actividades Sugeridas y recomendaciones para la edad de mi hijo(s) ',
            'this_order' => 2,
        ]);

        Topic::firstOrCreate([
            'name' => 'Canal LIA',
            'id' => 12,
            'slug' => 'canal_lia',
            'description' => 'Canal LIA',
            'this_order' => 2,
        ]);

        Topic::firstOrCreate([
            'name' => 'Podcast LIA',
            'id' => 13,
            'slug' => 'podcast_lia',
            'description' => 'Podcast LIA',
            'this_order' => 2,
        ]);

        Topic::firstOrCreate([
            'name' => 'Colectivo LIA',
            'id' => 14,
            'slug' => 'colectivo_lia',
            'description' => 'Colectivo LIA',
            'this_order' => 2,
        ]);

        Topic::firstOrCreate([
            'name' => 'Contenidos educativos hijos',
            'id' => 15,
            'slug' => 'contenidos_educativos_hijos',
            'description' => 'Contenidos educativos y cursos para mi hij@(s)',
            'this_order' => 2,
        ]);

        Topic::firstOrCreate([
            'name' => 'Contenidos educativos papas',
            'id' => 16,
            'slug' => 'contenidos_educativos_papas',
            'description' => 'Contenidos educativos y cursos para papás',
            'this_order' => 2,
        ]);

        Topic::firstOrCreate([
            'name' => 'Mundo LIA',
            'id' => 17,
            'slug' => 'mundo_lia',
            'description' => 'Mundo LIA: Videojuegos para mi hijo',
            'this_order' => 2,
        ]);

        Topic::firstOrCreate([
            'name' => 'No-Schooling',
            'id' => 18,
            'slug' => 'no_schooling',
            'description' => 'No-Schooling: Programa educativo no escolarizado. ',
            'this_order' => 2,
        ]);

        Topic::firstOrCreate([
            'name' => 'Experiencias LIA',
            'id' => 19,
            'slug' => 'experiencias_lia',
            'description' => 'Participación en Eventos / Experiencias LIA',
            'this_order' => 2,
        ]);
    }
}
