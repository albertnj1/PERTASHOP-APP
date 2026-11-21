<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $connection = $app['config']->get('database.default');
        $dbName = (string) $app['config']->get("database.connections.{$connection}.database");

        if (!str_ends_with($dbName, '_test')) {
            throw new \RuntimeException(
                "CRITICAL SAFETY VIOLATION: Test suite MUST run on a database ending with '_test'. Aborting BEFORE any migration or database operations. Current DB: '{$dbName}' on connection '{$connection}'."
            );
        }

        return $app;
    }
}
