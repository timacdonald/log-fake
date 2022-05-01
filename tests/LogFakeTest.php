<?php

declare(strict_types=1);

namespace Tests;

use Closure;
use Illuminate\Config\Repository as Config;
use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\Constraint\ExceptionMessage;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Stringable;
use Symfony\Component\VarDumper\VarDumper;
use Throwable;
use TiMacDonald\Log\ChannelFake;
use TiMacDonald\Log\LogFake;
use function assert;
use function config;
use function is_array;

/**
 * @small
 */
class LogFakeTest extends TestCase
{
    public const MESSAGE = 'Expected logged message';

    protected function setUp(): void
    {
        parent::setUp();

        $container = Container::setInstance(new Container());

        $container->singleton('config', fn (): Repository => new Config(['logging' => ['default' => 'stack']]));

        $container->singleton('log', fn (Container $app): LoggerInterface => new LogManager($app));

        Facade::setFacadeApplication($container);
    }

    public function testAssertLoggedFunc(): void
    {
        $log = new LogFake();

        self::assertFailsWithMessage(
            fn () => $log->assertLogged(fn ($level) => $level === 'info'),
            'Expected log was not created in the [stack] channel.'
        );
        $log->info('xxxx');
        $log->assertLogged(fn ($level) => $level === 'info');

        self::assertFailsWithMessage(
            fn () => $log->channel('channel')->assertLogged(fn ($level) => $level === 'info'),
            'Expected log was not created in the [channel] channel.'
        );
        $log->channel('channel')->info('xxxx');
        $log->channel('channel')->assertLogged(fn ($level) => $level === 'info');

        self::assertFailsWithMessage(
            fn () => $log->stack(['c1', 'c2'], 'name')->assertLogged(fn ($level) => $level === 'info'),
            'Expected log was not created in the [stack::name:c1,c2] channel.'
        );
        $log->stack(['c1', 'c2'], 'name')->info('xxxx');
        $log->stack(['c1', 'c2'], 'name')->assertLogged(fn ($level) => $level === 'info');
    }

    public function testAssertLoggedTimes(): void
    {
        $log = new LogFake();

        $log->info('xxxx');
        self::assertFailsWithMessage(
            fn () => $log->assertLoggedTimes(fn ($level) => $level === 'info', 2),
            'Expected log was not created [2] times in the [stack] channel. Instead was created [1] times.'
        );
        $log->assertLoggedTimes(fn ($level) => $level === 'info', 1);

        $log->channel('channel')->info('xxxx');
        self::assertFailsWithMessage(
            fn () => $log->channel('channel')->assertLoggedTimes(fn ($level) => $level === 'info', 2),
            'Expected log was not created [2] times in the [channel] channel. Instead was created [1] times.'
        );
        $log->channel('channel')->assertLoggedTimes(fn ($level) => $level === 'info', 1);

        $log->stack(['c1', 'c2'], 'name')->info('xxxx');
        self::assertFailsWithMessage(
            fn () => $log->stack(['c1', 'c2'], 'name')->assertLoggedTimes(fn ($level) => $level === 'info', 2),
            'Expected log was not created [2] times in the [stack::name:c1,c2] channel. Instead was created [1] times.'
        );
        $log->stack(['c1', 'c2'], 'name')->assertLoggedTimes(fn ($level) => $level === 'info', 1);
    }


    public function testAssertNotLogged(): void
    {
        $log = new LogFake();

        $log->assertNotLogged(fn ($level) => $level === 'info');
        $log->info('xxxx');
        self::assertFailsWithMessage(
            fn () => $log->assertNotLogged(fn ($level) => $level === 'info'),
            'Expected log was not created [0] times in the [stack] channel. Instead was created [1] times.'
        );

        $log->channel('channel')->assertNotLogged(fn ($level) => $level === 'info');
        $log->channel('channel')->info('xxxx');
        self::assertFailsWithMessage(
            fn () => $log->channel('channel')->assertNotLogged(fn ($level) => $level === 'info'),
            'Expected log was not created [0] times in the [channel] channel. Instead was created [1] times.'
        );

        $log->stack(['c1', 'c2'], 'name')->assertNotLogged(fn ($level) => $level === 'info');
        $log->stack(['c1', 'c2'], 'name')->info('xxxx');
        self::assertFailsWithMessage(
            fn () => $log->stack(['c1', 'c2'], 'name')->assertNotLogged(fn ($level) => $level === 'info'),
            'Expected log was not created [0] times in the [stack::name:c1,c2] channel. Instead was created [1] times.'
        );
    }

    public function testAssertNothingLogged(): void
    {
        $log = new LogFake();

        $log->assertNothingLogged();
        $log->info('xxxx');
        self::assertFailsWithMessage(
            fn () => $log->assertNothingLogged(),
            'Expected [0] logs to be created in the [stack] channel. Found [1] instead.'
        );

        $log->channel('channel')->assertNothingLogged();
        $log->channel('channel')->info('expected message');
        self::assertFailsWithMessage(
            fn () => $log->channel('channel')->assertNothingLogged(),
            'Expected [0] logs to be created in the [channel] channel. Found [1] instead.'
        );

        $log->stack(['c1', 'c2'], 'name')->assertNothingLogged();
        $log->stack(['c1', 'c2'], 'name')->info('xxxx');
        self::assertFailsWithMessage(
            fn () => $log->stack(['c1', 'c2'], 'name')->assertNothingLogged(),
            'Expected [0] logs to be created in the [stack::name:c1,c2] channel. Found [1] instead.'
        );
    }

    public function testAssertWasForgotten(): void
    {
        $log = new LogFake();

        self::assertFailsWithMessage(
            fn () => $log->assertWasForgotten(),
            'Expected the [stack] channel to be forgotten at least once. It was forgotten [0] times.'
        );
        $log->forgetChannel('stack');
        $log->assertWasForgotten();

        self::assertFailsWithMessage(
            fn () => $log->channel('channel')->assertWasForgotten(),
            'Expected the [channel] channel to be forgotten at least once. It was forgotten [0] times.',
        );
        $log->forgetChannel('channel');
        $log->channel('channel')->assertWasForgotten();

        // cannot assert against a stack.
    }

    public function testLogged(): void
    {
        $log = new LogFake();

        $this->assertTrue($log->logged('info')->isEmpty());
        $log->info('xxxx');
        $this->assertFalse($log->logged('info')->isEmpty());

        $this->assertTrue($log->channel('channel')->logged('info')->isEmpty());
        $log->channel('channel')->info('xxxx');
        $this->assertFalse($log->channel('channel')->logged('info')->isEmpty());

        $this->assertTrue($log->stack(['c1', 'c2'], 'name')->logged('info')->isEmpty());
        $log->stack(['c1', 'c2'], 'name')->info('xxxx');
        $this->assertFalse($log->stack(['c1', 'c2'], 'name')->logged('info')->isEmpty());
    }

    public function testLoggedWithCallback(): void
    {
        $log = new LogFake();

        $this->assertTrue($log->logged('info', fn (): bool => true)->isEmpty());
        $log->info('expected message');
        $this->assertFalse($log->logged('info', fn (): bool => true)->isEmpty());

        $this->assertTrue($log->channel('channel')->logged('info', fn (): bool => true)->isEmpty());
        $log->channel('channel')->info('expected message');
        $this->assertFalse($log->channel('channel')->logged('info', fn (): bool => true)->isEmpty());

        $this->assertTrue($log->stack(['c1', 'c2'], 'name')->logged('info', fn (): bool => true)->isEmpty());
        $log->stack(['c1', 'c2'], 'name')->info('expected message');
        $this->assertFalse($log->stack(['c1', 'c2'], 'name')->logged('info', fn (): bool => true)->isEmpty());
    }

    public function testLoggingLevelMethods(): void
    {
        $log = new LogFake();

        $log->emergency('emergency log');
        $log->alert('alert log');
        $log->critical('critical log');
        $log->error('error log');
        $log->warning('warning log');
        $log->info('info log');
        $log->notice('notice log');
        $log->debug('debug log');
        $log->log('custom', 'custom log');
        $log->write('custom_2', 'custom log 2');

        $log->assertLogged(fn (string $level, string $message): bool => $level === 'emergency' && $message === 'emergency log');
        $log->assertLogged(fn (string $level, string $message): bool => $level === 'alert' && $message === 'alert log');
        $log->assertLogged(fn (string $level, string $message): bool => $level === 'critical' && $message === 'critical log');
        $log->assertLogged(fn (string $level, string $message): bool => $level === 'error' && $message === 'error log');
        $log->assertLogged(fn (string $level, string $message): bool => $level === 'warning' && $message === 'warning log');
        $log->assertLogged(fn (string $level, string $message): bool => $level === 'info' && $message === 'info log');
        $log->assertLogged(fn (string $level, string $message): bool => $level === 'notice' && $message === 'notice log');
        $log->assertLogged(fn (string $level, string $message): bool => $level === 'debug' && $message === 'debug log');
        $log->assertLogged(fn (string $level, string $message): bool => $level === 'custom' && $message === 'custom log');
        $log->assertLogged(fn (string $level, string $message): bool => $level === 'custom_2' && $message === 'custom log 2');
    }

    public function assertChannelAndDriverMethodsCanBeUsedInterchangably(): void
    {
        $log = new LogFake();

        $log->driver('channel')->info('expected message');
        $log->channel('channel')->assertLogged(fn (string $level, ): bool => $level === 'info' && true);
    }

    public function testCurrentStackIsTakenIntoAccount(): void
    {
        $log = new LogFake();

        $log->stack(['bugsnag', 'sentry'], 'dev_team')->info('expected message');

        $log->assertNotLogged('info');
        $log->stack(['bugsnag', 'sentry'], 'dev_team')->assertLogged('info');
    }

    public function testCanHaveStackChannelsInAnyOrder(): void
    {
        $log = new LogFake();

        $log->stack(['bugsnag', 'sentry'], 'dev_team')->info('expected message');

        $log->assertNotLogged('info');
        $log->stack(['sentry', 'bugsnag'], 'dev_team')->assertLogged('info');
    }

    public function testDifferentiatesBetweenStacksWithANameAndThoseWithout(): void
    {
        $log = new LogFake();

        $log->stack(['bugsnag', 'sentry'], 'dev_team')->info('expected message');
        $log->stack(['bugsnag', 'sentry'])->alert('expected message');

        $log->stack(['sentry', 'bugsnag'], 'dev_team')->assertNotLogged('alert');
        $log->stack(['sentry', 'bugsnag'])->assertNotLogged('info');
    }

    public function testDifferentiatesBetweenStacksAndChannelsWithTheSameName(): void
    {
        $log = new LogFake();

        $log->stack(['bugsnag', 'sentry'])->info('expected message');
        $log->channel('bugsnag,sentry')->alert('expected message');

        $log->stack(['bugsnag', 'sentry'])->assertNotLogged('alert');
        $log->channel('bugsnag,sentry')->assertNotLogged('info');

        $log->stack(['bugsnag', 'sentry'], 'name')->info('expected message');
        $log->channel('name:bugsnag,sentry')->alert('expected message');

        $log->stack(['name', 'bugsnag', 'sentry'])->assertNotLogged('alert');
        $log->channel('name:bugsnag,sentry')->assertNotLogged('info');
    }

    public function testAssertLoggedInStackDotNotatesSortedChannels(): void
    {
        $log = new LogFake();

        try {
            $log->stack(['c', 'b', 'a'], 'name')->assertLogged('info', fn (string $message) => 'xxxx');
            $this->fail();
        } catch (Throwable $e) {
            $this->assertStringContainsString('An expected log with level [info] was not logged in the [stack::name:a,b,c] channel.', $e->getMessage());
        }
    }

    public function testClosuresProvideMessageAndContext(): void
    {
        $log = new LogFake();
        $log->info('expected message', ['key' => 'expected']);

        $items = $log->logged('info', function (string $message, array $context) {
            $this->assertSame('expected message', $message);
            $this->assertSame(['key' => 'expected'], $context);

            return true;
        });
        $this->assertTrue($items->isNotEmpty());
        $log->assertLogged('info', function (string $message, array $context) {
            $this->assertSame('expected message', $message);
            $this->assertSame(['key' => 'expected'], $context);

            return true;
        });
        $log->assertNotLogged('info', function (string $message, array $context) {
            $this->assertSame('expected message', $message);
            $this->assertSame(['key' => 'expected'], $context);

            return false;
        });
    }

    public function testSetDefaultDriver(): void
    {
        $log = new LogFake();
        $log->setDefaultDriver('expected-driver');

        $this->assertSame('expected-driver', config()->get('logging.default'));
    }

    public function testLoggedClosureWithNonBooleanReturn(): void
    {
        $log = new LogFake();
        $log->info('xxxx');

        $log->logged('info', function () {
            $this->assertTrue(true);

            return 1;
        });
    }

    public function testAssertionCallbacksRecieveTimesForgottenAsAParameter(): void
    {
        $log = new LogFake();
        $forgotten = [];

        $log->info('foo');
        $log->assertLogged('info', function (string $message, array $context, int $timesForgotten) use (&$forgotten) {
            if ($message === 'foo') {
                $forgotten[] = $timesForgotten;
            }

            return true;
        });
        $log->forgetChannel('stack');

        $log->info('bar');
        $log->assertLogged('info', function ($message, $context, $timesForgotten) use (&$forgotten) {
            if ($message === 'bar') {
                $forgotten[] = $timesForgotten;
            }

            return true;
        });
        $log->forgetChannel('stack');

        $log->info('baz');
        $log->assertLogged('info', function ($message, $context, $timesForgotten) use (&$forgotten) {
            if ($message === 'baz') {
                $forgotten[] = $timesForgotten;
            }

            return true;
        });
        $log->forgetChannel('stack');

        $this->assertSame([0, 1, 2], $forgotten);
    }

    public function testDummyMethods(): void
    {
        $log = new LogFake();

        $log->listen(function () {
            //
        });
        $log->extend('misc', function () {
            //
        });
        $log->setEventDispatcher(new class () implements Dispatcher {
            public function listen($events, $listener = null)
            {
                //
            }

            public function hasListeners($eventName)
            {
                return false;
            }

            public function subscribe($subscriber)
            {
                //
            }

            public function until($event, $payload = [])
            {
                return null;
            }

            public function dispatch($event, $payload = [], $halt = false)
            {
                return null;
            }

            public function push($event, $payload = [])
            {
                //
            }

            public function flush($event)
            {
                //
            }

            public function forget($event)
            {
                //
            }

            public function forgetPushed()
            {
                //
            }
        });
        $log->getEventDispatcher();
        $this->assertSame($log->getLogger(), $log->channel());
    }

    public function testItCanDumpDefaultChannel(): void
    {
        $log = new LogFake();
        $dumps = [];
        VarDumper::setHandler(static function (array $logs) use (&$dumps) {
            assert(is_array($dumps));

            $dumps[] = $logs;
        });

        $log->info('expected log 1');
        $log->debug('expected log 2');
        $log->channel('channel')->info('missing channel log');
        $log = $log->dump();

        $this->assertInstanceOf(ChannelFake::class, $log);
        $this->assertTrue(is_array($dumps));
        $this->assertCount(1, $dumps);
        $logs = $dumps[0];

        $this->assertTrue(is_array($logs));
        $this->assertCount(2, $logs);

        $this->assertSame([
            [
                'level' => 'info',
                'message' => 'expected log 1',
                'context' => [],
                'times_channel_has_been_forgotten_at_time_of_writing_log' => 0,
                'channel' => 'stack',
            ],
            [
                'level' => 'debug',
                'message' => 'expected log 2',
                'context' => [],
                'times_channel_has_been_forgotten_at_time_of_writing_log' => 0,
                'channel' => 'stack',
            ],
        ], $logs);

        VarDumper::setHandler(null);
    }

    public function testItCanDumpALevelForTheDefaultChannel(): void
    {
        $log = new LogFake();
        $dumps = [];
        VarDumper::setHandler(static function (array $logs) use (&$dumps) {
            assert(is_array($dumps));

            $dumps[] = $logs;
        });
        $log->info('expected log');
        $log->debug('missing log');
        $log->channel('channel')->info('missing channel log');
        $log = $log->dump('info');

        $this->assertInstanceOf(ChannelFake::class, $log);
        $this->assertTrue(is_array($dumps));
        $this->assertCount(1, $dumps);
        $logs = $dumps[0];
        $this->assertTrue(is_array($logs));
        $this->assertCount(1, $logs);
        $this->assertSame([
            'level' => 'info',
            'message' => 'expected log',
            'context' => [],
            'times_channel_has_been_forgotten_at_time_of_writing_log' => 0,
            'channel' => 'stack',
        ], $logs[0]);

        VarDumper::setHandler(null);
    }

    public function testItCanDumpAChannel(): void
    {
        $log = new LogFake();
        $dumps = [];
        VarDumper::setHandler(static function (array $logs) use (&$dumps) {
            assert(is_array($dumps));

            $dumps[] = $logs;
        });
        $log->info('missing log');
        $log->channel('unknown')->info('missing log');
        $log->channel('known')->info('expected log 1');
        $log->channel('known')->debug('expected log 2');
        $log = $log->channel('known')->dump();

        $this->assertInstanceOf(ChannelFake::class, $log);
        $this->assertTrue(is_array($dumps));
        $this->assertCount(1, $dumps);
        $logs = $dumps[0];
        $this->assertTrue(is_array($logs));
        $this->assertCount(2, $logs);
        $this->assertSame([
            [
                'level' => 'info',
                'message' => 'expected log 1',
                'context' => [],
                'times_channel_has_been_forgotten_at_time_of_writing_log' => 0,
                'channel' => 'known',
            ],
            [
                'level' => 'debug',
                'message' => 'expected log 2',
                'context' => [],
                'times_channel_has_been_forgotten_at_time_of_writing_log' => 0,
                'channel' => 'known',
            ],
        ], $logs);

        VarDumper::setHandler(null);
    }

    public function testItCanDumpALevelForAChannel(): void
    {
        $log = new LogFake();
        $dumps = [];
        VarDumper::setHandler(static function (array $logs) use (&$dumps) {
            assert(is_array($dumps));

            $dumps[] = $logs;
        });
        $log->info('missing log');
        $log->channel('unknown')->info('missing log');
        $log->channel('known')->info('expected log');
        $log->channel('known')->debug('missing log');
        $log = $log->channel('known')->dump('info');

        $this->assertInstanceOf(ChannelFake::class, $log);
        $this->assertTrue(is_array($dumps));
        $this->assertCount(1, $dumps);
        $logs = $dumps[0];
        $this->assertTrue(is_array($logs));
        $this->assertCount(1, $logs);
        $this->assertSame([
            [
                'level' => 'info',
                'message' => 'expected log',
                'context' => [],
                'times_channel_has_been_forgotten_at_time_of_writing_log' => 0,
                'channel' => 'known',
            ],
        ], $logs);

        VarDumper::setHandler(null);
    }

    public function testItCanDumpAllLogsForAllChannels(): void
    {
        $log = new LogFake();
        $dumps = [];
        VarDumper::setHandler(static function (array $logs) use (&$dumps) {
            assert(is_array($dumps));

            $dumps[] = $logs;
        });

        $log->info('expected log 1');
        $log->debug('expected log 2');
        $log->channel('channel')->info('expected log 3');
        $log->channel('channel')->debug('expected log 4');
        $log = $log->dumpAll();

        $this->assertInstanceOf(LogFake::class, $log);
        $this->assertTrue(is_array($dumps));
        $this->assertCount(1, $dumps);
        $logs = $dumps[0];

        $this->assertTrue(is_array($logs));
        $this->assertCount(4, $logs);

        $this->assertSame([
            [
                'level' => 'info',
                'message' => 'expected log 1',
                'context' => [],
                'times_channel_has_been_forgotten_at_time_of_writing_log' => 0,
                'channel' => 'stack',
            ],
            [
                'level' => 'debug',
                'message' => 'expected log 2',
                'context' => [],
                'times_channel_has_been_forgotten_at_time_of_writing_log' => 0,
                'channel' => 'stack',
            ],
            [
                'level' => 'info',
                'message' => 'expected log 3',
                'context' => [],
                'times_channel_has_been_forgotten_at_time_of_writing_log' => 0,
                'channel' => 'channel',
            ],
            [
                'level' => 'debug',
                'message' => 'expected log 4',
                'context' => [],
                'times_channel_has_been_forgotten_at_time_of_writing_log' => 0,
                'channel' => 'channel',
            ],
        ], $logs);

        VarDumper::setHandler(null);
    }

    public function testItCanDumpAllLogsForAllChannelsButFilterByLevel(): void
    {
        $log = new LogFake();
        $dumps = [];
        VarDumper::setHandler(static function (array $logs) use (&$dumps) {
            assert(is_array($dumps));

            $dumps[] = $logs;
        });

        $log->info('expected log 1');
        $log->debug('missing log');
        $log->channel('channel')->info('expected log 2');
        $log->channel('channel')->debug('missing log');
        $log = $log->dumpAll('info');

        $this->assertInstanceOf(LogFake::class, $log);
        $this->assertTrue(is_array($dumps));
        $this->assertCount(1, $dumps);
        $logs = $dumps[0];

        $this->assertTrue(is_array($logs));
        $this->assertCount(2, $logs);

        $this->assertSame([
            [
                'level' => 'info',
                'message' => 'expected log 1',
                'context' => [],
                'times_channel_has_been_forgotten_at_time_of_writing_log' => 0,
                'channel' => 'stack',
            ],
            [
                'level' => 'info',
                'message' => 'expected log 2',
                'context' => [],
                'times_channel_has_been_forgotten_at_time_of_writing_log' => 0,
                'channel' => 'channel',
            ],
        ], $logs);

        VarDumper::setHandler(null);
    }

    public function testItHandlesNullDriverConfig(): void
    {
        $log = new LogFake();
        config()->set('logging.default', null);

        $log->info('xxxx');
        $log->channel('null')->assertLogged('info');
    }

    public function testItCanLogStringableObjects(): void
    {
        $log = new LogFake();
        $stringable = new class () implements Stringable {
            public function __toString(): string
            {
                return 'expected message';
            }
        };

        $log->info($stringable);

        $this->assertSame($log->logged('info')->first()['message'], 'expected message');
    }

    public function testItAddsContextToLogs(): void
    {
        $log = new LogFake();

        $log->withContext(['foo' => 'xxxx'])
            ->withContext(['bar' => 'xxxx'])
            ->info('expected message', [
                'baz' => 'xxxx',
            ]);

        $this->assertSame($log->logged('info')->first()['context'], [
            'foo' => 'xxxx',
            'bar' => 'xxxx',
            'baz' => 'xxxx',
        ]);
    }

    public function testItCanClearContext(): void
    {
        $log = new LogFake();

        $log->withContext(['foo' => 'xxxx'])
            ->withoutContext()
            ->info('expected message', [
                'baz' => 'xxxx',
            ]);

        $this->assertSame($log->logged('info')->first()['context'], [
            'baz' => 'xxxx',
        ]);
    }


    public function testItCanAssertAChannelHasNotBeenForgotten(): void
    {
        $log = new LogFake();

        $log->assertWasNotForgotten();
        $log->forgetChannel('stack');
        try {
            $log->assertWasNotForgotten();
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Expected the [stack] channel to be forgotten [0] times. It was forgotten [1] times.'));
        }

        $log->channel('channel')->assertWasNotForgotten();
        $log->forgetChannel('channel');
        try {
            $log->channel('channel')->assertWasNotForgotten();
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Expected the [channel] channel to be forgotten [0] times. It was forgotten [1] times.'));
        }
    }

    public function testItCanFakeOnDemandChannels(): void
    {
        $log = new LogFake();

        $log->build([])->info('expected message');

        $log->channel('ondemand')->assertLogged('info', fn ($message) => $message === 'expected message');
    }

    public function testItCanRetrieveChannels(): void
    {
        $log = new LogFake();

        $channel = $log->channel('expected-channel');

        $this->assertSame(['expected-channel' => $channel], $log->getChannels());
    }

    public function testItCanBindItselfToTheContainer(): void
    {
        $this->assertNotInstanceOf(LogFake::class, app('log'));

        $log = LogFake::bind();

        $this->assertSame($log, app('log'));
    }

    public function testItHandlesNullLogger(): void
    {
        config()->set('logging.default', null);
        $log = new LogFake();

        $log->info('expected message');

        $log->channel('null')->assertLogged('info', fn (string $message) => $message === 'expected message');
    }

    public function testItResetsStackContextOnChannelBuild()
    {
        $log = new LogFake();

        $stack1 = $log->stack(['c1'], 'name');
        $stack1->withContext(['bound' => 'context']);
        $stack1->info('message 1', ['logged' => 'context']);
        $stack1->assertLogged('info', function (string $message, array $context) {
            return $message === 'message 1'
                && $context === ['bound' => 'context', 'logged' => 'context'];
        });

        $stack2 = $log->stack(['c1'], 'name');
        $stack2->info('message 2', ['logged' => 'context']);
        $stack2->assertLogged('info', function (string $message, array $context) {
            return $message === 'message 2'
                && $context === ['logged' => 'context'];
        });
    }

    public function testItDoesntUseTheSameChannelsInDriversThanItDoesForIndividualChannels()
    {
        $log = new LogFake();

        $log->channel('stderr')->info('expected message 1');
        try {
            $log->stack(['stderr'])->assertLoggedMessage('info', 'expected message 1');
            $this->fail();
        } catch (Throwable $e) {
            $this->assertStringContainsString('An expected log with level [info] was not logged in the [stack::unnamed:stderr] channel.', $e->getMessage());
        }

        $log->stack(['stderr'])->info('expected message 2');
        try {
            $log->channel('stderr')->assertLoggedMessage('info', 'expected message 2');
            $this->fail();
        } catch (Throwable $e) {
            $this->assertStringContainsString('An expected log with level [info] was not logged in the [stderr] channel.', $e->getMessage());
        }
    }

    public function testItGivesStacksANameWhenNoneIsProvided()
    {
        $log = new LogFake();

        try {
            $log->stack(['c1'])->assertLogged('info', fn (string $message) => $message === 'xxxx');
            $this->fail();
        } catch (Throwable $e) {
            $this->assertStringContainsString('An expected log with level [info] was not logged in the [stack::unnamed:c1] channel.', $e->getMessage());
        }
    }

    public function testItCanAssertTheCurrentContextForChannels(): void
    {
        $log = new LogFake();

        try {
            $log->assertCurrentContext(['foo' => 'bar']);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Expected to find the context [{"foo":"bar"}] in the [stack] channel. Found [{}] instead.'));
        }
        $log->withContext(['foo' => 'bar']);
        $log->assertCurrentContext(['foo' => 'bar']);

        try {
            $log->channel('channel')->assertCurrentContext(['foo' => 'bar']);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Expected to find the context [{"foo":"bar"}] in the [channel] channel. Found [{}] instead.'));
        }
        $log->channel('channel')->withContext(['foo' => 'bar']);
        $log->channel('channel')->assertCurrentContext(['foo' => 'bar']);
    }

    public function testItCanAssertTheCurrentContextForChannelsWithClosure(): void
    {
        $log = new LogFake();

        try {
            $log->assertCurrentContext(fn (array $context) => $context === ['foo' => 'bar']);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Unexpected context found in the [stack] channel. Found [{}].'));
        }
        $log->withContext(['foo' => 'bar']);
        $log->assertCurrentContext(fn (array $context) => $context === ['foo' => 'bar']);

        try {
            $log->channel('channel')->assertCurrentContext(fn (array $context) => $context === ['foo' => 'bar']);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Unexpected context found in the [channel] channel. Found [{}].'));
        }
        $log->channel('channel')->withContext(['foo' => 'bar']);
        $log->channel('channel')->assertCurrentContext(fn (array $context) => $context === ['foo' => 'bar']);
    }

    public function testItCannotAssertCurrentContextForStacks(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot call [Log::stack(...)->assertCurrentContext(...)] as stack contexts are reset each time they are resolved from the LogManager. Instead utilise [Log::stack(...)->assertHadContext(...)].');

        (new LogFake())->stack(['c1', 'c2'], 'name')->assertCurrentContext(['foo' => 'bar']);
    }

    public function testItCanAssertPreviousContext(): void
    {
        $log = new LogFake();

        $log->withContext(['foo' => 'bar']);
        $log->withContext(['foo' => 'bar', 'bar' => 'baz']);
        $log->withoutContext();
        $log->assertCurrentContext([]);
        $log->assertHadContext(['foo' => 'bar']);
        $log->assertHadContext(['foo' => 'bar', 'bar' => 'baz']);
        try {
            $log->assertHadContext(['bar' => 'baz']);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Expected to find the context [{"bar":"baz"}] in the [stack] channel but did not.'));
        }
        $log->withContext(['bar' => 'baz']);
        $log->assertHadContext(['bar' => 'baz']);

        $log->channel('channel')->withContext(['foo' => 'bar']);
        $log->channel('channel')->withContext(['foo' => 'bar', 'bar' => 'baz']);
        $log->channel('channel')->withoutContext();
        $log->channel('channel')->assertCurrentContext([]);
        $log->channel('channel')->assertHadContext(['foo' => 'bar']);
        $log->channel('channel')->assertHadContext(['foo' => 'bar', 'bar' => 'baz']);
        try {
            $log->channel('channel')->assertHadContext(['bar' => 'baz']);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Expected to find the context [{"bar":"baz"}] in the [channel] channel but did not.'));
        }
        $log->channel('channel')->withContext(['bar' => 'baz']);
        $log->channel('channel')->assertHadContext(['bar' => 'baz']);

        $log->stack(['c1', 'c2'], 'name')->withContext(['foo' => 'bar']);
        $log->stack(['c1', 'c2'], 'name')->withContext(['foo' => 'bar', 'bar' => 'baz']);
        $log->stack(['c1', 'c2'], 'name')->withoutContext();
        $log->stack(['c1', 'c2'], 'name')->assertHadContext(['foo' => 'bar']);
        $log->stack(['c1', 'c2'], 'name')->assertHadContext(['foo' => 'bar', 'bar' => 'baz']);
        try {
            $log->stack(['c1', 'c2'], 'name')->assertHadContext(['bar' => 'baz']);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Expected to find the context [{"bar":"baz"}] in the [stack::name:c1,c2] channel but did not.'));
        }
        $log->stack(['c1', 'c2'], 'name')->withContext(['bar' => 'baz']);
        $log->stack(['c1', 'c2'], 'name')->assertHadContext(['bar' => 'baz']);
    }

    public function testItCanAssertPreviousContextWithClosure(): void
    {
        $log = new LogFake();

        $log->withContext(['foo' => 'bar']);
        $log->withContext(['foo' => 'bar', 'bar' => 'baz']);
        $log->withoutContext();
        $log->assertCurrentContext([]);
        $log->assertHadContext(fn (array $context) => $context === ['foo' => 'bar']);
        $log->assertHadContext(fn (array $context) => $context === ['foo' => 'bar', 'bar' => 'baz']);
        try {
            $log->assertHadContext(fn (array $context) => $context === ['bar' => 'baz']);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Unexpected context found in the [stack] channel.'));
        }
        $log->withContext(['bar' => 'baz']);
        $log->assertHadContext(fn (array $context) => $context === ['bar' => 'baz']);

        $log->channel('channel')->withContext(['foo' => 'bar']);
        $log->channel('channel')->withContext(['foo' => 'bar', 'bar' => 'baz']);
        $log->channel('channel')->withoutContext();
        $log->channel('channel')->assertCurrentContext([]);
        $log->channel('channel')->assertHadContext(fn (array $context) => $context === ['foo' => 'bar']);
        $log->channel('channel')->assertHadContext(fn (array $context) => $context === ['foo' => 'bar', 'bar' => 'baz']);
        try {
            $log->channel('channel')->assertHadContext(fn (array $context) => $context === ['bar' => 'baz']);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Unexpected context found in the [channel] channel.'));
        }
        $log->channel('channel')->withContext(['bar' => 'baz']);
        $log->channel('channel')->assertHadContext(fn (array $context) => $context === ['bar' => 'baz']);

        $log->stack(['c1', 'c2'], 'name')->withContext(['foo' => 'bar']);
        $log->stack(['c1', 'c2'], 'name')->withContext(['foo' => 'bar', 'bar' => 'baz']);
        $log->stack(['c1', 'c2'], 'name')->withoutContext();
        $log->stack(['c1', 'c2'], 'name')->assertHadContext(fn (array $context) => $context === ['foo' => 'bar']);
        $log->stack(['c1', 'c2'], 'name')->assertHadContext(fn (array $context) => $context === ['foo' => 'bar', 'bar' => 'baz']);
        try {
            $log->stack(['c1', 'c2'], 'name')->assertHadContext(fn (array $context) => $context === ['bar' => 'baz']);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Unexpected context found in the [stack::name:c1,c2] channel.'));
        }
        $log->stack(['c1', 'c2'], 'name')->withContext(['bar' => 'baz']);
        $log->stack(['c1', 'c2'], 'name')->assertHadContext(fn (array $context) => $context === ['bar' => 'baz']);
    }

    public function testItCanAssertAChannelIsCurrentlyFogotten(): void
    {
        $log = new LogFake();

        try {
            $log->assertChannelIsCurrentlyForgotten('channel');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Unable to assert that the [channel] channel has been forgotten. The channel was never built.'));
        }

        $log->channel('channel')->info('xxxx');

        try {
            $log->assertChannelIsCurrentlyForgotten('channel');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Expected to find the [channel] channel to be forgotten. It was not.'));
        }

        $log->forgetChannel('channel');
        $log->assertChannelIsCurrentlyForgotten('channel');

        $log->channel('channel')->info('xxxx');

        try {
            $log->assertChannelIsCurrentlyForgotten('channel');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Expected to find the [channel] channel to be forgotten. It was not.'));
        }
    }

    public function testItClearsContextWhenAChannelIsForgotten(): void
    {
        $log = new LogFake();
        $log->channel('channel')->withContext(['foo' => 'bar']);
        $log->forgetChannel('channel');
        $log->channel('channel')->info('expected message');

        $log->channel('channel')->assertLogged('info', function (string $message, array $context) {
            return $context === [];
        });
    }

    private static function assertFailsWithMessage(Closure $callback, string $message): void
    {
        try {
            $callback();
            self::fail();
        } catch (ExpectationFailedException $exception) {
            self::assertThat($exception, new ExceptionMessage($message));
        }
    }
}
