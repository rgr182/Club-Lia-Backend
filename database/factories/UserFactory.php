<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\User;
use Carbon\Carbon;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(User::class, function (Faker $faker) {
    return [
        'username' => $faker->userName,
        'name' => $faker->name,
        'second_name' =>$faker->name,
        'last_name' => $faker->name,
        'second_last_name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'avatar' => '',
        'password' => "123456",
        'verified_email' => true,
        'remember_token' => Str::random(10),
        'role_id' => 3
    ];
});
