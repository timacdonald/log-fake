<?php

declare(strict_types=1);

namespace Tests;

use Closure;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Log\LoggerInterface;
use Throwable;

class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = Container::setInstance(new Container());

        $container->singleton('config', fn (): Repository => new Repository(['logging' => ['default' => 'stack']]));

        $container->singleton('log', fn (Container $app): LoggerInterface => new LogManager($app)); /** @phpstan-ignore-line */

        Facade::setFacadeApplication($container); /** @phpstan-ignore-line */
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
