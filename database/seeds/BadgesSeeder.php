<?php

use App\Badge;
use Illuminate\Database\Seeder;

class BadgesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Badge::updateOrCreate([
            "id" => 1,
            "name" => "Lo puedes hacer mejor",
            "description" => "Lo puedes hacer mejor",
            "badge" => "assets/images/homeworks/svg1/1.svg",
            "score" => ""
        ]);

        Badge::updateOrCreate([
            "id" => 2,
            "name" => "Has mejorado bastante",
            "description" => "Has mejorado bastante",
            "badge" => "assets/images/homeworks/svg2/2.svg",
            "score" => ""
        ]);

        Badge::updateOrCreate([
            "id" => 3,
            "name" => "Revisado, buen trabajo",
            "description" => "Revisado, buen trabajo",
            "badge" => "assets/images/homeworks/svg3/3.svg",
            "score" => ""
        ]);

        Badge::updateOrCreate([
            "id" => 4,
            "name" => "Excelente, sigue asi",
            "description" => "Excelente, sigue asi",
            "badge" => "assets/images/homeworks/svg4/4.svg",
            "score" => ""
        ]);

        Badge::updateOrCreate([
            "id" => 5,
            "name" => "Tarea a tiempo, muchas gracias",
            "description" => "Tarea a tiempo, muchas gracias",
            "badge" => "assets/images/homeworks/svg5/5.svg",
            "score" => ""
        ]);

        Badge::updateOrCreate([
            "id" => 6,
            "name" => "Revisa de nuevo, casi lo logras",
            "description" => "Revisa de nuevo, casi lo logras",
            "badge" => "assets/images/homeworks/svg6/6.svg",
            "score" => ""
        ]);

        Badge::updateOrCreate([
            "id" => 7,
            "name" => "Mejora tus tiempos de entrega",
            "description" => "Mejora tus tiempos de entrega",
            "badge" => "assets/images/homeworks/svg7/7.svg",
            "score" => ""
        ]);

        Badge::updateOrCreate([
            "id" => 8,
            "name" => "Gracias por el apoyo en casa",
            "description" => "Gracias por el apoyo en casa",
            "badge" => "assets/images/homeworks/svg8/8.svg"
        ]);

        Badge::updateOrCreate([
            "id" => 9,
            "name" => "Muy creativo",
            "description" => "Muy creativo",
            "badge" => "assets/images/homeworks/svg9/9.svg"
        ]);

        Badge::updateOrCreate([
            "id" => 10,
            "name" => "Gracias por tus evidencias",
            "description" => "Gracias por tus evidencias",
            "badge" => "assets/images/homeworks/svg10/10.svg"
        ]);
    }
}