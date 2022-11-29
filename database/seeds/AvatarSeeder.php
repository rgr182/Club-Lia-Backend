<?php

use App\Avatar;
use Illuminate\Database\Seeder;

class AvatarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Avatar::updateOrCreate([
            'id'  => 1,
            'name'  => 'Lalo',
            'description' => 'Lalo es talentoso, optimista y conciliador. Ama la música y la programación de videojuegos. Siempre tiene una nueva aventura que compartir. Es muy amigable y popular ¡Es excepcional!',
            'type' => 2,
            'path' => 'assets/images/avatars/avatarLalo.png'
        ]);

        Avatar::updateOrCreate([
            'id'  => 2,
            'name'  => 'Lia',
            'description' => 'Lía es emprendedora, aventurera y leal. Le encanta observar, indagar y crear, lo que la lleva a solucionar nuevos desafíos a través de sus inventos. ¡Ella es extraordinaria!',
            'type' => 2,
            'path' => 'assets/images/avatars/avatarLia.png'
        ]);

        Avatar::updateOrCreate([
            'id'  => 4,
            'name'  => 'Tony',
            'description' => 'Tony es activo, detallista y honrado. Le gusta practicar deportes, siempre comer sano y mantener su bienestar físico. Usa su pasión y estadísticas de los deportes para determinar y descifrar resultados y retos.¡Es estupendo!',
            'type' => 2,
            'path' => 'assets/images/avatars/avatar4.png'
        ]);

        Avatar::updateOrCreate([
            'id'  => 5,
            'name'  => 'Mei',
            'description' => 'Mei es creativa, responsable y justa. Le encanta resolver enigmas y encontrar respuestas a preguntas difíciles, siempre analizando y pensando con cuidado. ¡Ella es fantástica!',
            'type' => 2,
            'path' => 'assets/images/avatars/avatar1.png'
        ]);

        Avatar::updateOrCreate([
            'id'  => 6,
            'name'  => 'Sofi',
            'description' => 'Sofi es ingeniosa, valiente y comprometida. Le gustan la danza y la gimnasia. Disfruta de proteger los derechos de los animales. ¡Es maravillosa!',
            'type' => 2,
            'path' => 'assets/images/avatars/avatar3.png'
        ]);

        Avatar::updateOrCreate([
            'id'  => 7,
            'name'  => 'Roy',
            'description' => 'Roy es analista, enigmático y compasivo. Le encantan las ciencias y disfruta resolver cualquier reto que se le presente, siempre promoviendo el trabajo en equipo. ¡Él es admirable!',
            'type' => 2,
            'path' => 'assets/images/avatars/avatar0.png'
        ]);

        Avatar::updateOrCreate([
            'id'  => 8,
            'name'  => 'Dania',
            'description' => 'Dania es organizada, divertida y pacificadora. Le encantan las aventuras en la naturaleza. Busca promover la paz, el cuidado de la naturaleza y la justicia social. ¡Ella es sorprendente!',
            'type' => 2,
            'path' => 'assets/images/avatars/avatar2.png'
        ]);

        Avatar::updateOrCreate([
            'id'  => 9,
            'name'  => 'Lalo ',
            'description' => 'Lalo es talentoso, optimista y conciliador. Ama la música y la programación de videojuegos. Siempre tiene una nueva aventura que compartir. Es muy amigable y popular ¡Es excepcional!',
            'type' => 1,
            'path' => 'assets/images/avatars/avatarLaloP.png'
        ]);

        Avatar::updateOrCreate([
            'id'  => 10,
            'name'  => 'Lia',
            'description' => 'Lía es emprendedora, aventurera y leal. Le encanta observar, indagar y crear, lo que la lleva a solucionar nuevos desafíos a través de sus inventos. ¡Ella es extraordinaria!',
            'type' => 1,
            'path' => 'assets/images/avatars/avatarLiaP.png'
        ]);

        Avatar::updateOrCreate([
            'id'  => 11,
            'name'  => 'Roby',
            'description' => 'Roby es perspicaz, indagador, curioso y bondadoso. Le gusta siempre aprender cosas nuevas. ¡Él es genial!',
            'type' => 1,
            'path' => 'assets/images/avatars/boot.png'
        ]);
    }
}
