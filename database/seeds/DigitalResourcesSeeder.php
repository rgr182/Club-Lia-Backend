<?php

use App\DigitalResources;
use Illuminate\Database\Seeder;

class DigitalResourcesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DigitalResources::firstOrCreate([
            'id'  => 1,
            'bloque'  => 1,
            'grade' => 2,
            'level' => 1,
            'name' => 'Arte, sensaciones y emociones.',
            'url_resource' => 'https://docs.google.com/presentation/d/1nz76Mgmm5rh30F9LjenYHy1FuhAdMH-e0AC9eRrJlqc/copy#slide=id.gc03b29a1ac_0_19',
            'id_materia_base' => 8,
            'id_category' => 20,
            'description' => ''
        ]);
        DigitalResources::firstOrCreate([
            'id'  => 2,
            'bloque'  => 1,
            'grade' => 2,
            'level' => 1,
            'name' => 'La magia de los colores.',
            'url_resource' => 'https://www.educandy.com/site/resource.php?activity-code=928fa',
            'id_materia_base' => 8,
            'id_category' => 20,
            'description' => ''
        ]);
        DigitalResources::firstOrCreate([
            'id'  => 3,
            'bloque'  => 1,
            'grade' => 4,
            'level' => 1,
            'name' => 'El Super Sistema Nervioso',
            'url_resource' => 'https://view.genial.ly/60ecc658c325ca0d725ce1d0',
            'id_materia_base' => 5,
            'id_category' => 9,
            'description' => ''
        ]);
        DigitalResources::firstOrCreate([
            'id'  => 4,
            'bloque'  => 1,
            'grade' => 4,
            'level' => 1,
            'name' => 'Alimentación sana',
            'url_resource' => 'https://wordwall.net/es/resource/19073102',
            'id_materia_base' => 5,
            'id_category' => 5,
            'description' => ''
        ]);
        DigitalResources::firstOrCreate([
            'id'  => 5,
            'bloque'  => 1,
            'grade' => 4,
            'level' => 1,
            'name' => 'El cuerpo humano la máquina perfecta',
            'url_resource' => 'https://view.genial.ly/60edc42fc1e7000d678fd0df',
            'id_materia_base' => 5,
            'id_category' => 20,
            'description' => ''
        ]);
        DigitalResources::firstOrCreate([
            'id'  => 6,
            'bloque'  => 1,
            'grade' => 4,
            'level' => 1,
            'name' => 'las vacunas',
            'url_resource' => 'https://view.genial.ly/60ee1478bd186c0d79d199b6',
            'id_materia_base' => 5,
            'id_category' => 9,
            'description' => ''
        ]);
        DigitalResources::firstOrCreate([
            'id'  => 7,
            'bloque'  => 1,
            'grade' => 4,
            'level' => 1,
            'name' => 'La ciencia y las vacunas',
            'url_resource' => 'https://view.genial.ly/60ee093eae4ede0d80344431/interactive-content-la-historia-de-las-vacunas',
            'id_materia_base' => 5,
            'id_category' => 12,
            'description' => ''
        ]);
        DigitalResources::firstOrCreate([
            'id'  => 8,
            'bloque'  => 1,
            'grade' => 4,
            'level' => 1,
            'name' => 'La cartilla Nacional de Salud',
            'url_resource' => 'https://docs.google.com/presentation/d/17QpZqUq3JeHoogm8Jb7KRiQJ64oz6WUFtdMqSNAMc2Y/copy#slide=id.ge4cd71cb5e_0_17',
            'id_materia_base' => 5,
            'id_category' => 24,
            'description' => ''
        ]);
        DigitalResources::firstOrCreate([
            'id'  => 9,
            'bloque'  => 1,
            'grade' => 4,
            'level' => 1,
            'name' => 'Caracteres sexuales del hombre y la mujer',
            'url_resource' => 'https://view.genial.ly/60ec988cc325ca0d725cdf82/interactive-content-los-caracteres-sexuales-masculinos-y-femeninos',
            'id_materia_base' => 5,
            'id_category' => 20,
            'description' => ''
        ]);
        DigitalResources::firstOrCreate([
            'id'  => 10,
            'bloque'  => 1,
            'grade' => 4,
            'level' => 1,
            'name' => 'El apartato reproductor femenino',
            'url_resource' => 'https://view.genial.ly/60ec988cc325ca0d725cdf82/interactive-content-los-caracteres-sexuales-masculinos-y-femeninos',
            'id_materia_base' => 5,
            'id_category' => 24,
            'description' => ''
        ]);
        DigitalResources::firstOrCreate([
            'id'  => 11,
            'bloque'  => 1,
            'grade' => 4,
            'level' => 1,
            'name' => 'La reproducción de las plantas',
            'url_resource' => 'https://view.genial.ly/60ee2af18517c90daed32148',
            'id_materia_base' => 5,
            'id_category' => 9,
            'description' => ''
        ]);
        DigitalResources::firstOrCreate([
            'id'  => 12,
            'bloque'  => 1,
            'grade' => 4,
            'level' => 1,
            'name' => 'Ovíparo o vivíparo',
            'url_resource' => 'https://view.genial.ly/60ecbc02a582d10d6de2ff82',
            'id_materia_base' => 5,
            'id_category' => 9,
            'description' => ''
        ]);
        DigitalResources::firstOrCreate([
            'id'  => 13,
            'bloque'  => 1,
            'grade' => 4,
            'level' => 1,
            'name' => 'Los hongos y bacterias',
            'url_resource' => 'https://wordwall.net/es/resource/19125750',
            'id_materia_base' => 5,
            'id_category' => 9,
            'description' => ''
        ]);
        DigitalResources::firstOrCreate([
            'id'  => 14,
            'bloque'  => 1,
            'grade' => 4,
            'level' => 1,
            'name' => 'Hongos bacterias y sus reinos',
            'url_resource' => 'https://wordwall.net/es/resource/19125750',
            'id_materia_base' => 5,
            'id_category' => 9,
            'description' => ''
        ]);
        DigitalResources::firstOrCreate([
            'id'  => 15,
            'bloque'  => 1,
            'grade' => 4,
            'level' => 1,
            'name' => 'Prevención de accidentes',
            'url_resource' => 'https://wordwall.net/es/resource/19076029/prevenci%c3%b3n-de-accidentes',
            'id_materia_base' => 5,
            'id_category' => 24,
            'description' => ''
        ]);
        DigitalResources::firstOrCreate([
            'id'  => 16,
            'bloque'  => 1,
            'grade' => 4,
            'level' => 1,
            'name' => 'Las partes de la flor',
            'url_resource' => 'https://view.genial.ly/60ef2f09e3a65d0d03fcdf63',
            'id_materia_base' => 5,
            'id_category' => 24,
            'description' => ''
        ]);
        DigitalResources::firstOrCreate([
            'id'  => 17,
            'bloque'  => 1,
            'grade' => 5,
            'level' => 1,
            'name' => 'Las partes de la flor',
            'url_resource' => 'https://view.genial.ly/60ef2f09e3a65d0d03fcdf63',
            'id_materia_base' => 5,
            'id_category' => 24,
            'description' => ''
        ]);
        DigitalResources::firstOrCreate([
            'id'  => 18,
            'bloque'  => 1,
            'grade' => 5,
            'level' => 1,
            'name' => '¿Cómo mantener la salud?',
            'url_resource' => 'https://view.genial.ly/610d6ef350b0820d15af0337',
            'id_materia_base' => 5,
            'id_category' => 20,
            'description' => ''
        ]);
        DigitalResources::firstOrCreate([
            'id'  => 19,
            'bloque'  => 1,
            'grade' => 5,
            'level' => 1,
            'name' => 'Prevengo el sobrepeso, la obesidad',
            'url_resource' => 'https://wordwall.net/es/resource/19901045',
            'id_materia_base' => 5,
            'id_category' => 9,
            'description' => ''
        ]);
        DigitalResources::firstOrCreate([
            'id'  => 20,
            'bloque'  => 1,
            'grade' => 5,
            'level' => 1,
            'name' => 'Conceptos de nutrición',
            'url_resource' => 'https://wordwall.net/es/resource/19891102',
            'id_materia_base' => 5,
            'id_category' => 9,
            'description' => ''
        ]);
        DigitalResources::firstOrCreate([
            'id'  => 21,
            'bloque'  => 1,
            'grade' => 5,
            'level' => 1,
            'name' => 'El tabaco las drogas y el alcohol y sus efectos en el organismo',
            'url_resource' => 'https://view.genial.ly/61115667b26c1b0da5e2df62',
            'id_materia_base' => 5,
            'id_category' => 9,
            'description' => ''
        ]);
        DigitalResources::firstOrCreate([
            'id'  => 22,
            'bloque'  => 1,
            'grade' => 5,
            'level' => 1,
            'name' => 'Las adicciones y sus riesgos',
            'url_resource' => 'https://view.genial.ly/611168fa7d250f0d21b38772',
            'id_materia_base' => 5,
            'id_category' => 24,
            'description' => ''
        ]);
        DigitalResources::firstOrCreate([
            'id'  => 23,
            'bloque'  => 1,
            'grade' => 5,
            'level' => 1,
            'name' => 'El ciclo de reproducción femenino',
            'url_resource' => 'https://wordwall.net/es/resource/19901593',
            'id_materia_base' => 5,
            'id_category' => 9,
            'description' => ''
        ]);
        DigitalResources::firstOrCreate([
            'id'  => 24,
            'bloque'  => 1,
            'grade' => 5,
            'level' => 1,
            'name' => 'Fecundación embarazo y parto',
            'url_resource' => 'https://wordwall.net/es/resource/19985128',
            'id_materia_base' => 5,
            'id_category' => 24,
            'description' => ''
        ]);
        DigitalResources::firstOrCreate([
            'id'  => 25,
            'bloque'  => 1,
            'grade' => 5,
            'level' => 1,
            'name' => 'La biodiversidad',
            'url_resource' => 'https://view.genial.ly/61118542b26c1b0da5e2e2f5',
            'id_materia_base' => 5,
            'id_category' => 17,
            'description' => ''
        ]);
        DigitalResources::firstOrCreate([
            'id'  => 26,
            'bloque'  => 1,
            'grade' => 5,
            'level' => 1,
            'name' => 'Las especies endémicas de México',
            'url_resource' => 'https://wordwall.net/es/resource/20031332',
            'id_materia_base' => 5,
            'id_category' => 17,
            'description' => ''
        ]);
        DigitalResources::firstOrCreate([
            'id'  => 27,
            'bloque'  => 1,
            'grade' => 5,
            'level' => 1,
            'name' => 'Las especies en peligro de extinción en México',
            'url_resource' => 'https://wordwall.net/es/resource/20031759',
            'id_materia_base' => 5,
            'id_category' => 13,
            'description' => ''
        ]);
        DigitalResources::firstOrCreate([
            'id'  => 28,
            'bloque'  => 1,
            'grade' => 5,
            'level' => 1,
            'name' => 'Los diversos ecosistemas y la riqueza natural de México',
            'url_resource' => 'https://wordwall.net/es/resource/20125742',
            'id_materia_base' => 5,
            'id_category' => 5,
            'description' => ''
        ]);
        DigitalResources::firstOrCreate([
            'id'  => 29,
            'bloque'  => 1,
            'grade' => 5,
            'level' => 1,
            'name' => 'El aprovechamiento de los recursos naturales y su impacto.',
            'url_resource' => 'https://wordwall.net/es/resource/20126994',
            'id_materia_base' => 5,
            'id_category' => 13,
            'description' => ''
        ]);
    }
}