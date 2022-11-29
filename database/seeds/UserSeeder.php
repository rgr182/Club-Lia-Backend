<?php

use Illuminate\Database\Seeder;
use Caffeinated\Shinobi\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::firstOrCreate([
            'name' => 'Admin',
            'id' => 1,
            'slug' => 'admin',
            'description' => 'Administrador del sistema',
            'this_order' => 1,
            'special' => 'all-access'
        ]);

        Role::firstOrCreate([
            'name' => 'Ventas',
            'id' => 2,
            'slug' => 'ventas',
            'description' => 'Ventas',
            'this_order' => 2,
        ]);

        Role::firstOrCreate([
            'id' => 3,
            'name' => 'Admin Escuela',
            'slug' => 'admin_escuela',
            'description' => 'Administador de la escuela',
            'this_order' => 3,
        ]);

        Role::firstOrCreate([
            'id' => 4,
            'name' => 'Maestro',
            'slug' => 'maestro',
            'description' => 'Maestro de grupo',
            'this_order' => 4,
        ]);

        Role::firstOrCreate([
            'id' => 5,
            'name' => 'Alumno',
            'slug' => 'alumno',
            'description' => 'Alumno',
            'this_order' => 5,
        ]);

        Role::firstOrCreate([
            'id' => 10,
            'name' => 'Padre',
            'slug' => 'padre',
            'description' => 'Padre del Alumno',
            'this_order' => 6,
        ]);

        Role::firstOrNew([
            'id' => 13,
            'name' => 'Preescolar',
            'slug' => 'preescolar',
            'description' => 'Preescolar',
            'this_order' => 7,
        ]);

        Role::firstOrCreate([
            'id' => 6,
            'name' => 'Alumno Secundaria',
            'slug' => 'alumno_secundaria',
            'description' => 'Alumno de Secundaria',
            'this_order' => 8,
        ]);

        Role::firstOrCreate([
            'id' => 7,
            'name' => 'Maestro Preescolar',
            'slug' => 'maestro_preescolar',
            'description' => 'Maestro de Preescolar',
            'this_order' => 9,
        ]);

        Role::firstOrCreate([
            'id' => 8,
            'name' => 'Maestro Secundaria',
            'slug' => 'maestro_secundaria',
            'description' => 'Maestro de Secundaria',
            'this_order' => 10,
        ]);

        Role::firstOrCreate([
            'id' => 9,
            'name' => 'Director Escuela',
            'slug' => 'director_escuela',
            'description' => 'Director de Escuela',
            'this_order' => 11,
        ]);

        Role::firstOrCreate([
            'id' => 17,
            'name' => 'ProfesorSummit2021',
            'slug' => 'profesor_summit_2021',
            'description' => 'Profesor Summit 2021',
            'this_order' => 12,
        ]);

        Role::firstOrCreate([
            'id' => 18,
            'name' => 'AlumnoE0',
            'slug' => 'alumnoe0',
            'description' => 'AlumnoE0',
            'this_order' => 13,
        ]);

        Role::firstOrCreate([
            'id' => 19,
            'name' => 'AlumnoE1',
            'slug' => 'alumnoe1',
            'description' => 'AlumnoE1',
            'this_order' => 14,
        ]);

        Role::firstOrCreate([
            'id' => 20,
            'name' => 'AlumnoE2',
            'slug' => 'alumnoe2',
            'description' => 'AlumnoE2',
            'this_order' => 15,
        ]);

        Role::firstOrCreate([
            'id' => 21,
            'name' => 'AlumnoE3',
            'slug' => 'alumnoe3',
            'description' => 'AlumnoE3',
            'this_order' => 16,
        ]);

        Role::firstOrCreate([
            'id' => 22,
            'name' => 'MaestroE1',
            'slug' => 'maestroe1',
            'description' => 'MaestroE1',
            'this_order' => 17,
        ]);

        Role::firstOrCreate([
            'id' => 23,
            'name' => 'MaestroE2',
            'slug' => 'maestroe2',
            'description' => 'MaestroE2',
            'this_order' => 18,
        ]);

        Role::firstOrCreate([
            'id' => 24,
            'name' => 'MaestroE3',
            'slug' => 'maestroe3',
            'description' => 'MaestroE3',
            'this_order' => 19,
        ]);

        Role::firstOrCreate([
            'id' => 25,
            'name' => 'Escuela Invitado',
            'slug' => 'Escuela-I',
            'description' => 'Escuela Membresía Invitado',
            'this_order' => 20,
        ]);

        Role::firstOrCreate([
            'id' => 26,
            'name' => 'Escuela Mensual',
            'slug' => 'Escuela-M',
            'description' => 'Escuela Membresía Mensual',
            'this_order' => 21,
        ]);

        Role::firstOrCreate([
            'id' => 27,
            'name' => 'Escuela Anual',
            'slug' => 'Escuela-A',
            'description' => 'Escuela Membresía Anual',
            'this_order' => 22,
        ]);

        Role::firstOrCreate([
            'id' => 28,
            'name' => 'Maestro Invitado',
            'slug' => 'Maestro-I',
            'description' => 'Maestro Membresía Invitado',
            'this_order' => 23,
        ]);

        Role::firstOrCreate([
            'id' => 29,
            'name' => 'Maestro Mensual',
            'slug' => 'Maestro-M',
            'description' => 'Maestro Membresía Mensual',
            'this_order' => 24,
        ]);

        Role::firstOrCreate([
            'id' => 30,
            'name' => 'Maestro Anual',
            'slug' => 'Maestro-A',
            'description' => 'Maestro Membresía Anual',
            'this_order' => 25,
        ]);

        Role::firstOrCreate([
            'id' => 31,
            'name' => 'Padre Invitado',
            'slug' => 'Padre-I',
            'description' => 'Padre Membresía Invitado',
            'this_order' => 26,
        ]);

        Role::firstOrCreate([
            'id' => 32,
            'name' => 'Padre Mensual',
            'slug' => 'Padre-M',
            'description' => 'Padre Membresía Mensual',
            'this_order' => 27,
        ]);

        Role::firstOrCreate([
            'id' => 33,
            'name' => 'Padre Anual',
            'slug' => 'Padre-A',
            'description' => 'Padre Membresía Anual',
            'this_order' => 28,
        ]);

        Role::firstOrCreate([
            'id' => 34,
            'name' => 'Alumno Invitado',
            'slug' => 'Alumno-I',
            'description' => 'Alumno Membresía Invitado',
            'this_order' => 29,
        ]);

        Role::firstOrCreate([
            'id' => 35,
            'name' => 'Alumno Mensual',
            'slug' => 'Alumno-M',
            'description' => 'Alumno Membresía Mensual',
            'this_order' => 30,
        ]);

        Role::firstOrCreate([
            'id' => 36,
            'name' => 'Alumno Anual',
            'slug' => 'Alumno-A',
            'description' => 'Alumno Membresía Anual',
            'this_order' => 31,
        ]);

        Role::firstOrCreate([
            'id' => 37,
            'name' => 'Donadores',
            'slug' => 'donadores',
            'description' => 'Donadores',
            'this_order' => 32,
        ]);

        $password = bcrypt('Admin123456');
        $password = str_replace("$2y$", "$2a$", $password);

        App\User::firstOrCreate([
            'username' => 'admin',
            'name' => 'Administrador',
            'second_name' => '',
            'last_name' => 'System',
            'second_last_name' => '',
            'email' => 'lcruz@arkusnexus.com',
            'avatar' => '',
            'password' => $password,
            'verified_email' => true,
            'role_id' => 1
        ]);

        //factory(App\User::class, 15)->create();
    }
}
