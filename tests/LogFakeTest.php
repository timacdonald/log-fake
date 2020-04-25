<?php

namespace Tests;

use Illuminate\Contracts\Config\Repository;
use stdClass;
use TiMacDonald\Log\LogFake;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Config\Repository as Config;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\Constraint\ExceptionMessage;

class LogFakeTest extends TestCase
{
    const MESSAGE = 'Expected logged message';

    public function setUp(): void
    {
        parent::setUp();

        Container::setInstance(new Container)->singleton('config', function (): Repository {
            return new Config(['logging' => ['default' => 'stack']]);
        });
    }

    public function testAssertLogged(): void
    {
        $log = new LogFake;

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
        $log = new LogFake;

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
        $log = new LogFake;

        try {
            $log->assertLogged('info', function () {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was not logged in stack.'));
        }

        try {
            $log->channel('channel')->assertLogged('info', function () {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was not logged in channel.'));
        }

        try {
            $log->stack(['channel'], 'name')->assertLogged('info', function () {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was not logged in Stack:name.channel.'));
        }
    }

    public function testAssertLoggedTimesWithCallback(): void
    {
        $log = new LogFake;

        try {
            $log->assertLoggedTimes('info', 42, function () {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was logged 0 times instead of 42 times in stack.'));
        }

        try {
            $log->channel('channel')->assertLoggedTimes('info', 42, function () {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was logged 0 times instead of 42 times in channel.'));
        }

        try {
            $log->stack(['channel'], 'name')->assertLoggedTimes('info', 42, function () {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was logged 0 times instead of 42 times in Stack:name.channel.'));
        }

        try {
            $log->stack(['channel'], 'name')->assertLoggedTimes('info', 42, function () {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was logged 0 times instead of 42 times in Stack:name.channel.'));
        }
    }

    public function testAssertLoggedMessage(): void
    {
        $log = new LogFake;

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
        $log = new LogFake;

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
        $log = new LogFake;

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
        $log = new LogFake;

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
        $log = new LogFake;
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
        $log = new LogFake;
        $log->info(self::MESSAGE);
        $log->channel('channel')->info(self::MESSAGE);
        $log->stack(['channel'], 'name')->info(self::MESSAGE);

        try {
            $log->assertNotLogged('info', function () {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The unexpected log with level [info] was logged in stack.'));
        }

        try {
            $log->channel('channel')->assertNotLogged('info', function () {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The unexpected log with level [info] was logged in channel.'));
        }

        try {
            $log->stack(['channel'], 'name')->assertNotLogged('info', function () {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The unexpected log with level [info] was logged in Stack:name.channel.'));
        }
    }

    public function testAssertNothingLogged(): void
    {
        $log = new LogFake;
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
        $log = new LogFake;
        $this->assertInstanceOf(LoggerInterface::class, $log->channel('channel'));
    }

    public function testLogged(): void
    {
        $log = new LogFake;

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
        $log = new LogFake;

        $this->assertTrue($log->logged('info', function () {
            return true;
        })->isEmpty());
        $log->info(self::MESSAGE);
        $this->assertFalse($log->logged('info', function () {
            return true;
        })->isEmpty());

        $this->assertTrue($log->channel('channel')->logged('info', function () {
            return true;
        })->isEmpty());
        $log->channel('channel')->info(self::MESSAGE);
        $this->assertFalse($log->channel('channel')->logged('info', function () {
            return true;
        })->isEmpty());

        $this->assertTrue($log->stack(['channel'], 'name')->logged('info', function () {
            return true;
        })->isEmpty());
        $log->stack(['channel'], 'name')->info(self::MESSAGE);
        $this->assertFalse($log->stack(['channel'], 'name')->logged('info', function () {
            return true;
        })->isEmpty());
    }

    public function testHasLogged(): void
    {
        $log = new LogFake;

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
        $log = new LogFake;

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
        $log = new LogFake;

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

        $log->assertLogged('emergency', function (string $message) {
            return $message === 'emergency log';
        });
        $log->assertLogged('alert', function (string $message) {
            return $message === 'alert log';
        });
        $log->assertLogged('critical', function (string $message) {
            return $message === 'critical log';
        });
        $log->assertLogged('error', function (string $message) {
            return $message === 'error log';
        });
        $log->assertLogged('warning', function (string $message) {
            return $message === 'warning log';
        });
        $log->assertLogged('info', function (string $message) {
            return $message === 'info log';
        });
        $log->assertLogged('notice', function (string $message) {
            return $message === 'notice log';
        });
        $log->assertLogged('debug', function (string $message) {
            return $message === 'debug log';
        });
        $log->assertLogged('custom', function (string $message) {
            return $message === 'custom log';
        });
        $log->assertLogged('custom_2', function (string $message) {
            return $message === 'custom log 2';
        });
    }

    public function assertChannelAndDriverMethodsCanBeUsedInterchangably(): void
    {
        $log = new LogFake;

        $log->driver('channel')->info(self::MESSAGE);
        $log->channel('channel')->assertLogged('info', function () {
            return true;
        });
    }

    public function testCurrentStackIsTakenIntoAccount(): void
    {
        $log = new LogFake;

        $log->stack(['bugsnag', 'sentry'], 'dev_team')->info(self::MESSAGE);

        $log->assertNotLogged('info');
        $log->stack(['bugsnag', 'sentry'], 'dev_team')->assertLogged('info');
    }

    public function testCanHaveStackChannelsInAnyOrder(): void
    {
        $log = new LogFake;

        $log->stack(['bugsnag', 'sentry'], 'dev_team')->info(self::MESSAGE);

        $log->assertNotLogged('info');
        $log->stack(['sentry', 'bugsnag'], 'dev_team')->assertLogged('info');
    }

    public function testDifferentiatesBetweenStacksWithANameAndThoseWithout(): void
    {
        $log = new LogFake;

        $log->stack(['bugsnag', 'sentry'], 'dev_team')->info(self::MESSAGE);
        $log->stack(['bugsnag', 'sentry'])->alert(self::MESSAGE);

        $log->stack(['sentry', 'bugsnag'], 'dev_team')->assertNotLogged('alert');
        $log->stack(['sentry', 'bugsnag'])->assertNotLogged('info');
    }

    public function testDifferentiatesBetweenStacksAndChannelsWithTheSameName(): void
    {
        $log = new LogFake;

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
        $this->assertSame('Stack:name.a.b.c', (new LogFake)->stack(['c', 'b', 'a'], 'name')->currentChannel());
    }

    public function testClosuresProvideMessageAndContext(): void
    {
        $log = new LogFake;
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
        $log = new LogFake;
        $log->setDefaultDriver('expected-driver');

        $this->assertSame('expected-driver', config()->get('logging.default'));
    }
}
