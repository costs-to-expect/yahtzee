<?php

declare(strict_types=1);

namespace Tests;

use App\Jobs\BackfillStats;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * RefreshDatabase runs migrate:fresh, so a test run that picked up the development
     * database (the container's real DB_* variables beat phpunit.xml's) would wipe it.
     * Refuse to boot unless it is the in-memory SQLite one, this runs before anything
     * touches the database.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        $default = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$default}.database");

        if ($default !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException(
                "Refusing to run tests against the [{$default}] database [{$database}]: they would wipe it. Tests must use in-memory SQLite."
            );
        }

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // A request a test has not faked is a failing test, never a call to the real API.
        Http::preventStrayRequests();

        // Visiting the home page or the stats starts the job that collects a player's older games, which reads the
        // API for hours of games, a test that is not about it only wants to know it was started. The tests of the job
        // run it themselves.
        Bus::fake([BackfillStats::class]);
    }
}
