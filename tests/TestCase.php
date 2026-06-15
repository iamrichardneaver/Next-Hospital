<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'mysql'
            && config('database.connections.mysql.database') === 'nexthospital') {
            $this->fail(
                'Refusing to run tests against the live nexthospital database. '
                . 'phpunit.xml must use sqlite (:memory:) or a dedicated test database.'
            );
        }
    }
}
