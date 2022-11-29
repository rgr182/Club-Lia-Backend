<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\LicenseType;
use Faker\Generator as Faker;

$factory->define(LicenseType::class, function (Faker $faker) {
    return [
        'description_license_type' => $faker->name,
    ];
});
