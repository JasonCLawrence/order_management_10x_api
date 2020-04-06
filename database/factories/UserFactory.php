<?php

use Faker\Generator as Faker;

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

$factory->define(App\User::class, function (Faker $faker) {
    return [
        'first_name' => $faker->name,
        'last_name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'email_verified_at' => now(),
        'password' => Hash::make('password'), // secret
        'remember_token' => str_random(10),
    ];
});

$factory->afterCreatingState(App\User::class, 'admin', function ($user, $faker) {
    //$user->assignRoles(['administrator']);
});

$factory->afterCreatingState(App\User::class, 'driver', function ($user, $faker) {
    //$user->assignRoles(['driver']);
});
