<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\School;
use Ramsey\Uuid\Uuid;
use Faker\Generator as Faker;

$factory->define(School::class, function (Faker $faker) {
    return [
        'id' => $faker->randomNumber(),
        'name' => $faker->name,
        'description' => $faker->text,
    ];
});
