<?php

use Faker\Generator as Faker;

$factory->define(App\Warehouse::class, function (Faker $faker) {
    return [
        'name' => "Test Warehouse"
    ];
});
