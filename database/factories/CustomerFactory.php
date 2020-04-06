<?php

use Faker\Generator as Faker;

$factory->define(App\Customer::class, function (Faker $faker) {
    return [
        'name' => "Test Customer",
        'email' => "customer@test.com",
        'address' => 'The Moon'
    ];
});
