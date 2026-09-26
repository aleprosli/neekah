<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * RefreshDatabase drops every table before the suite runs. If the test
     * environment ever resolves to a real database (a cached config.php is
     * how it happened), stop before any trait gets to drop anything.
     *
     * @return array<class-string, class-string>
     */
    protected function setUpTraits(): array
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException("Refusing to run tests against [{$connection}:{$database}]: tests must use sqlite :memory:.");
        }

        return parent::setUpTraits();
    }
}
