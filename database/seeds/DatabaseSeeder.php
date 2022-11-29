<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
         $this->call(UserSeeder::class);
         $this->call(TopicSeeder::class);
//         $this->call(SchoolSeeder::class);
//         $this->call(LicenseTypeSeeder::class);
//         $this->call(LicenseSeeder::class);
    }
}
