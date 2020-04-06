<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function dump($data)
    {
        fwrite(STDERR, print_r($data, TRUE));
    }
}
