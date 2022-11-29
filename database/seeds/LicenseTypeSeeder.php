<?php

use App\LicenseType;
use Illuminate\Database\Seeder;

class LicenseTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        LicenseType::firstOrCreate([
            'title' => 'Registro sin costo Alumno',
            'description_license_type' => 'Licencia para Padres e hijos sin costo',
            'price' => '0' ,
            'category_id' => 'padres'
        ]);

        LicenseType::firstOrCreate([
            'title' => 'Registro Mensual Alumno',
            'description_license_type' => 'Licencia para Padres e hijos mensual',
            'price' => '250' ,
            'category_id' => 'padres'
        ]);

        LicenseType::firstOrCreate([
            'title' => 'Registro Anual Alumno',
            'description_license_type' => 'Licencia para Padres e hijos anual',
            'price' => '2500' ,
            'category_id' => 'padres'
        ]);

        LicenseType::firstOrCreate([
            'title' => 'Registro sin costo Maestro',
            'description_license_type' => 'Licencia para Maestros sin costo',
            'price' => '0' ,
            'category_id' => 'maestros'
        ]);

        LicenseType::firstOrCreate([
            'title' => 'Registro Mensual Maestro',
            'description_license_type' => 'Licencia para Maestros mensual',
            'price' => '500' ,
            'category_id' => 'maestros'
        ]);

        LicenseType::firstOrCreate([
            'title' => 'Registro Anual Maestro',
            'description_license_type' => 'Licencia para Maestros anual',
            'price' => '5000' ,
            'category_id' => 'maestros'
        ]);

        LicenseType::firstOrCreate([
            'title' => 'Registro sin costo Escuela',
            'description_license_type' => 'Licencia para Escuelas sin costo',
            'price' => '0' ,
            'category_id' => 'escuelas'
        ]);

        LicenseType::firstOrCreate([
            'title' => 'Registro Mensual Escuela',
            'description_license_type' => 'Licencia para Escuelas mensual',
            'price' => '10000' ,
            'category_id' => 'escuelas'
        ]);

        LicenseType::firstOrCreate([
            'title' => 'Registro Anual Escuela',
            'description_license_type' => 'Licencia para escuelas anual',
            'price' => '100000' ,
            'category_id' => 'escuelas'
        ]);
    }
}
