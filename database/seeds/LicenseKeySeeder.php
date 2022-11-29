<?php

use Illuminate\Database\Seeder;
use App\LicenseKey;

class LicenseKeySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        LicenseKey::create([
            'license_id' => 'c2119b6b-844d-4e6e-b698-b687b8a8a678',
            'user_id' => 'c1a22696-0769-4bce-bdf9-7ab796cda251',
        ]);
    }
}
