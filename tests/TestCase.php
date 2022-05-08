<?php

declare(strict_types=1);

namespace Tests;

use Closure;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Throwable;

class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $app = Container::setInstance(new Container());

        $app->singleton('config', fn () => new Repository(['logging' => ['default' => 'stack']]));
        $app->singleton('log', fn () => new LogManager($app)); /** @phpstan-ignore-line */

        Facade::setFacadeApplication($app); /** @phpstan-ignore-line */
        Facade::clearResolvedInstances();
    }

    protected static function assertFailsWithMessage(Closure $callback, string $message): void
    {
        try {
            $callback();
            self::fail('The log fake assertion did not fail as expected.');
        } catch (Throwable $exception) {
            self::assertStringStartsWith($message, $exception->getMessage());
        }
    }
}
