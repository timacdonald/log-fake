<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Config\Repository as Config;

use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\Constraint\ExceptionMessage;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\VarDumper\VarDumper;
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

        Container::setInstance(new Container())->singleton('config', static function (): Repository {
            return new Config(['logging' => ['default' => 'stack']]);
        });
    }

    public function testAssertLogged(): void
    {
        $log = new LogFake();

        try {
            $log->assertLogged('info');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was not logged in stack.'));
        }

        try {
            $log->channel('channel')->assertLogged('info');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was not logged in channel.'));
        }

        try {
            $log->stack(['channel'], 'name')->assertLogged('info');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was not logged in Stack:name.channel.'));
        }
    }

    public function testAssertLoggedWithCount(): void
    {
        $log = new LogFake();

        try {
            $log->assertLogged('info', 1);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was logged 0 times instead of 1 times in stack.'));
        }

        try {
            $log->channel('channel')->assertLogged('info', 1);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was logged 0 times instead of 1 times in channel.'));
        }

        try {
            $log->stack(['channel'], 'name')->assertLogged('info', 1);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was logged 0 times instead of 1 times in Stack:name.channel.'));
        }
    }

    public function testAssertLoggedWithCallback(): void
    {
        $log = new LogFake();

        try {
            $log->assertLogged('info', static function () {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was not logged in stack.'));
        }

        try {
            $log->channel('channel')->assertLogged('info', static function () {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was not logged in channel.'));
        }

        try {
            $log->stack(['channel'], 'name')->assertLogged('info', static function () {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was not logged in Stack:name.channel.'));
        }
    }

    public function testAssertLoggedTimesWithCallback(): void
    {
        $log = new LogFake();

        try {
            $log->assertLoggedTimes('info', 42, static function () {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was logged 0 times instead of 42 times in stack.'));
        }

        try {
            $log->channel('channel')->assertLoggedTimes('info', 42, static function () {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was logged 0 times instead of 42 times in channel.'));
        }

        try {
            $log->stack(['channel'], 'name')->assertLoggedTimes('info', 42, static function () {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was logged 0 times instead of 42 times in Stack:name.channel.'));
        }

        try {
            $log->stack(['channel'], 'name')->assertLoggedTimes('info', 42, static function () {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was logged 0 times instead of 42 times in Stack:name.channel.'));
        }
    }

    public function testAssertLoggedMessage(): void
    {
        $log = new LogFake();

        try {
            $log->assertLoggedMessage('info', 'expected message');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was not logged in stack'));
        }

        $log->info('expected message');
        $log->assertLoggedMessage('info', 'expected message');
    }

    public function testAssertLoggedTimes(): void
    {
        $log = new LogFake();

        try {
            $log->assertLoggedTimes('info');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was logged 0 times instead of 1 times in stack.'));
        }

        $log->info('expected info log');
        $log->assertLoggedTimes('info');

        try {
            $log->assertLoggedTimes('info', 2);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was logged 1 times instead of 2 times in stack.'));
        }

        $log->info('expected info log');
        $log->assertLoggedTimes('info', 2);
    }

    public function testAssertLoggedTimesInChannel(): void
    {
        $log = new LogFake();

        try {
            $log->channel('channel')->assertLoggedTimes('info');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was logged 0 times instead of 1 times in channel.'));
        }

        $log->channel('channel')->log('info', 'expected info log');
        $log->channel('channel')->assertLoggedTimes('info');

        try {
            $log->channel('channel')->assertLoggedTimes('info', 2);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was logged 1 times instead of 2 times in channel.'));
        }

        $log->channel('channel')->info('expected info log');
        $log->channel('channel')->assertLoggedTimes('info', 2);
    }

    public function testAssertLoggedTimesInStack(): void
    {
        $log = new LogFake();

        try {
            $log->stack(['channel'], 'name')->assertLoggedTimes('info');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was logged 0 times instead of 1 times in Stack:name.'));
        }

        $log->stack(['channel'], 'name')->log('info', 'expected info log');
        $log->stack(['channel'], 'name')->assertLoggedTimes('info');

        try {
            $log->stack(['channel'], 'name')->assertLoggedTimes('info', 2);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was logged 1 times instead of 2 times in Stack:name.channel.'));
        }

        $log->stack(['channel'], 'name')->info('expected info log');
        $log->stack(['channel'], 'name')->assertLoggedTimes('info', 2);
    }

    public function testAssertNotLogged(): void
    {
        $log = new LogFake();
        $log->info(self::MESSAGE);
        $log->channel('channel')->info(self::MESSAGE);
        $log->stack(['channel'], 'name')->info(self::MESSAGE);

        try {
            $log->assertNotLogged('info');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The unexpected log with level [info] was logged in stack.'));
        }

        try {
            $log->channel('channel')->assertNotLogged('info');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The unexpected log with level [info] was logged in channel.'));
        }

        try {
            $log->stack(['channel'], 'name')->assertNotLogged('info');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The unexpected log with level [info] was logged in Stack:name.channel.'));
        }
    }

    public function testAssertNotLoggedWithCallback(): void
    {
        $log = new LogFake();
        $log->info(self::MESSAGE);
        $log->channel('channel')->info(self::MESSAGE);
        $log->stack(['channel'], 'name')->info(self::MESSAGE);

        try {
            $log->assertNotLogged('info', static function () {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The unexpected log with level [info] was logged in stack.'));
        }

        try {
            $log->channel('channel')->assertNotLogged('info', static function () {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The unexpected log with level [info] was logged in channel.'));
        }

        try {
            $log->stack(['channel'], 'name')->assertNotLogged('info', static function () {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The unexpected log with level [info] was logged in Stack:name.channel.'));
        }
    }

    public function testAssertNothingLogged(): void
    {
        $log = new LogFake();
        $log->info(self::MESSAGE);
        $log->channel('channel')->info(self::MESSAGE);
        $log->stack(['channel'], 'name')->info(self::MESSAGE);

        try {
            $log->assertNothingLogged();
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Logs were created in stack.'));
        }

        try {
            $log->channel('channel')->assertNothingLogged();
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Logs were created in channel.'));
        }

        try {
            $log->stack(['channel'], 'name')->assertNothingLogged();
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Logs were created in Stack:name.channel.'));
        }
    }

    public function testChannelLoggerInstanceOfLoggerInterface(): void
    {
        $log = new LogFake();
        $this->assertInstanceOf(LoggerInterface::class, $log->channel('channel'));
    }

    public function testLogged(): void
    {
        $log = new LogFake();

        $this->assertTrue($log->logged('info')->isEmpty());
        $log->info(self::MESSAGE);
        $this->assertFalse($log->logged('info')->isEmpty());

        $this->assertTrue($log->channel('channel')->logged('info')->isEmpty());
        $log->channel('channel')->info(self::MESSAGE);
        $this->assertFalse($log->channel('channel')->logged('info')->isEmpty());

        $this->assertTrue($log->stack(['channel'], 'name')->logged('info')->isEmpty());
        $log->stack(['channel'], 'name')->info(self::MESSAGE);
        $this->assertFalse($log->stack(['channel'], 'name')->logged('info')->isEmpty());
    }

    public function testLoggedWithCallback(): void
    {
        $log = new LogFake();

        $this->assertTrue($log->logged('info', static function () {
            return true;
        })->isEmpty());
        $log->info(self::MESSAGE);
        $this->assertFalse($log->logged('info', static function () {
            return true;
        })->isEmpty());

        $this->assertTrue($log->channel('channel')->logged('info', static function () {
            return true;
        })->isEmpty());
        $log->channel('channel')->info(self::MESSAGE);
        $this->assertFalse($log->channel('channel')->logged('info', static function () {
            return true;
        })->isEmpty());

        $this->assertTrue($log->stack(['channel'], 'name')->logged('info', static function () {
            return true;
        })->isEmpty());
        $log->stack(['channel'], 'name')->info(self::MESSAGE);
        $this->assertFalse($log->stack(['channel'], 'name')->logged('info', static function () {
            return true;
        })->isEmpty());
    }

    public function testHasLogged(): void
    {
        $log = new LogFake();

        $this->assertFalse($log->hasLogged('info'));
        $log->info(self::MESSAGE);
        $this->assertTrue($log->hasLogged('info'));

        $this->assertFalse($log->channel('channel')->hasLogged('info'));
        $log->channel('channel')->info(self::MESSAGE);
        $this->assertTrue($log->channel('channel')->hasLogged('info'));

        $this->assertFalse($log->stack(['channel'], 'name')->hasLogged('info'));
        $log->stack(['channel'], 'name')->info(self::MESSAGE);
        $this->assertTrue($log->stack(['channel'], 'name')->hasLogged('info'));
    }

    public function testHasNotLogged(): void
    {
        $log = new LogFake();

        $this->assertTrue($log->hasNotLogged('info'));
        $log->info(self::MESSAGE);
        $this->assertFalse($log->hasNotLogged('info'));

        $this->assertTrue($log->channel('channel')->hasNotLogged('info'));
        $log->channel('channel')->info(self::MESSAGE);
        $this->assertFalse($log->channel('channel')->hasNotLogged('info'));

        $this->assertTrue($log->stack(['channel'], 'name')->hasNotLogged('info'));
        $log->stack(['channel'], 'name')->info(self::MESSAGE);
        $this->assertFalse($log->stack(['channel'], 'name')->hasNotLogged('info'));
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

        $log->assertLogged('emergency', static function (string $message) {
            return $message === 'emergency log';
        });
        $log->assertLogged('alert', static function (string $message) {
            return $message === 'alert log';
        });
        $log->assertLogged('critical', static function (string $message) {
            return $message === 'critical log';
        });
        $log->assertLogged('error', static function (string $message) {
            return $message === 'error log';
        });
        $log->assertLogged('warning', static function (string $message) {
            return $message === 'warning log';
        });
        $log->assertLogged('info', static function (string $message) {
            return $message === 'info log';
        });
        $log->assertLogged('notice', static function (string $message) {
            return $message === 'notice log';
        });
        $log->assertLogged('debug', static function (string $message) {
            return $message === 'debug log';
        });
        $log->assertLogged('custom', static function (string $message) {
            return $message === 'custom log';
        });
        $log->assertLogged('custom_2', static function (string $message) {
            return $message === 'custom log 2';
        });
    }

    public function assertChannelAndDriverMethodsCanBeUsedInterchangably(): void
    {
        $log = new LogFake();

        $log->driver('channel')->info(self::MESSAGE);
        $log->channel('channel')->assertLogged('info', static function () {
            return true;
        });
    }

    public function testCurrentStackIsTakenIntoAccount(): void
    {
        $log = new LogFake();

        $log->stack(['bugsnag', 'sentry'], 'dev_team')->info(self::MESSAGE);

        $log->assertNotLogged('info');
        $log->stack(['bugsnag', 'sentry'], 'dev_team')->assertLogged('info');
    }

    public function testCanHaveStackChannelsInAnyOrder(): void
    {
        $log = new LogFake();

        $log->stack(['bugsnag', 'sentry'], 'dev_team')->info(self::MESSAGE);

        $log->assertNotLogged('info');
        $log->stack(['sentry', 'bugsnag'], 'dev_team')->assertLogged('info');
    }

    public function testDifferentiatesBetweenStacksWithANameAndThoseWithout(): void
    {
        $log = new LogFake();

        $log->stack(['bugsnag', 'sentry'], 'dev_team')->info(self::MESSAGE);
        $log->stack(['bugsnag', 'sentry'])->alert(self::MESSAGE);

        $log->stack(['sentry', 'bugsnag'], 'dev_team')->assertNotLogged('alert');
        $log->stack(['sentry', 'bugsnag'])->assertNotLogged('info');
    }

    public function testDifferentiatesBetweenStacksAndChannelsWithTheSameName(): void
    {
        $log = new LogFake();

        $log->stack(['bugsnag', 'sentry'])->info(self::MESSAGE);
        $log->channel('bugsnag.sentry')->alert(self::MESSAGE);

        $log->stack(['bugsnag', 'sentry'])->assertNotLogged('alert');
        $log->channel('bugsnag.sentry')->assertNotLogged('info');

        $log->stack(['bugsnag', 'sentry'], 'name')->info(self::MESSAGE);
        $log->channel('name.bugsnag.sentry')->alert(self::MESSAGE);

        $log->stack(['name', 'bugsnag', 'sentry'])->assertNotLogged('alert');
        $log->channel('name.bugsnag.sentry')->assertNotLogged('info');
    }

    public function testAssertLoggedInStackDotNotatesSortedChannels(): void
    {
        $logFake = new LogFake();

        $logFake->stack(['c', 'b', 'a'], 'name')->info('expected message');

        $this->assertSame('Stack:name.a.b.c', $logFake->allLogs()[0]['channel']);
    }

    public function testClosuresProvideMessageAndContext(): void
    {
        $log = new LogFake();
        $log->info(self::MESSAGE, ['key' => 'expected']);

        $items = $log->logged('info', function (string $message, array $context) {
            $this->assertSame(self::MESSAGE, $message);
            $this->assertSame(['key' => 'expected'], $context);

            return true;
        });
        $this->assertTrue($items->isNotEmpty());
        $log->assertLogged('info', function (string $message, array $context) {
            $this->assertSame(self::MESSAGE, $message);
            $this->assertSame(['key' => 'expected'], $context);

            return true;
        });
        $log->assertNotLogged('info', function (string $message, array $context) {
            $this->assertSame(self::MESSAGE, $message);
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

    public function testDummyMethods(): void
    {
        $logFake = new LogFake();

        $logFake->listen(function () {
            //
        });
        $logFake->extend('misc', function () {
            //
        });
        $logFake->setEventDispatcher(new class () implements Dispatcher {
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
        $logFake->getEventDispatcher();
        $this->assertSame($logFake->getLogger(), $logFake);
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
                'channel' => 'stack',
            ],
            [
                'level' => 'debug',
                'message' => 'expected log 2',
                'context' => [],
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

        $this->assertInstanceOf(LogFake::class, $log);
        $this->assertTrue(is_array($dumps));
        $this->assertCount(1, $dumps);
        $logs = $dumps[0];
        $this->assertTrue(is_array($logs));
        $this->assertCount(1, $logs);
        $this->assertSame([
            'level' => 'info',
            'message' => 'expected log',
            'context' => [],
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
                'channel' => 'known',
            ],
            [
                'level' => 'debug',
                'message' => 'expected log 2',
                'context' => [],
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

        $this->assertInstanceOf(LogFake::class, $log);
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
                'channel' => 'stack',
            ],
            [
                'level' => 'debug',
                'message' => 'expected log 2',
                'context' => [],
                'channel' => 'stack',
            ],
            [
                'level' => 'info',
                'message' => 'expected log 3',
                'context' => [],
                'channel' => 'channel',
            ],
            [
                'level' => 'debug',
                'message' => 'expected log 4',
                'context' => [],
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
                'channel' => 'stack',
            ],
            [
                'level' => 'info',
                'message' => 'expected log 2',
                'context' => [],
                'channel' => 'channel',
            ],
        ], $logs);

        VarDumper::setHandler(null);
    }

    public function testItCannotCallDumpAllFromChannel(): void
    {
        $log = new LogFake();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('LogFake::dumpAll() should not be called from a channel.');

        $log->channel('channel')->dumpAll();
    }

    public function testItCannotCallDdAllFromAChannel(): void
    {
        $log = new LogFake();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('LogFake::ddAll() should not be called from a channel.');

        $log->channel('channel')->ddAll();
    }
}
