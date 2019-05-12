<?php

namespace Tests;

use stdClass;
use TiMacDonald\Log\LogFake;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Config\Repository as Config;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\Constraint\ExceptionMessage;

class LogFakeTest extends TestCase
{
    protected $message = 'Oh Hai Mark!';

    public function setUp()
    {
        parent::setUp();

        Container::setInstance(new Container)->singleton('config', function () {
            return new Config(['logging' => ['default' => 'stack']]);
        });
    }

    public function testAssertLogged()
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

    public function testAssertLoggedWithNumericCallback()
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

    public function testAssertLoggedWithCallback()
    {
        $log = new LogFake;

        try {
            $log->assertLogged('info', function ($message) {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was not logged in stack.'));
        }

        try {
            $log->channel('channel')->assertLogged('info', function ($message) {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was not logged in channel.'));
        }

        try {
            $log->stack(['channel'], 'name')->assertLogged('info', function ($message) {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was not logged in Stack:name.channel.'));
        }
    }

    public function testAssertLoggedWithCallbackMultipleTimes()
    {
        $log = new LogFake;

        try {
            $log->assertLogged('info', function ($message) {
                return true;
            }, 42);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was logged 0 times instead of 42 times in stack.'));
        }

        try {
            $log->channel('channel')->assertLogged('info', function ($message) {
                return true;
            }, 42);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was logged 0 times instead of 42 times in channel.'));
        }

        try {
            $log->stack(['channel'], 'name')->assertLogged('info', function ($message) {
                return true;
            }, 42);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was logged 0 times instead of 42 times in Stack:name.channel.'));
        }

        try {
            $log->stack(['channel'], 'name')->assertLogged('info', function ($message) {
                return true;
            }, 42);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was logged 0 times instead of 42 times in Stack:name.channel.'));
        }
    }

    public function testAssertLoggedTimes()
    {
        $log = new LogFake;

        try {
            $log->assertLoggedTimes('info', 1);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was logged 0 times instead of 1 times in stack.'));
        }

        try {
            $log->channel('channel')->assertLoggedTimes('info', 1);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was logged 0 times instead of 1 times in channel.'));
        }

        try {
            $log->stack(['channel'], 'name')->assertLoggedTimes('info', 1);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected log with level [info] was logged 0 times instead of 1 times in Stack:name.channel.'));
        }
    }

    public function testAssertNotLogged()
    {
        $log = new LogFake;
        $log->info($this->message);
        $log->channel('channel')->info($this->message);
        $log->stack(['channel'], 'name')->info($this->message);

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

    public function testAssertNotLoggedWithCallback()
    {
        $log = new LogFake;
        $log->info($this->message);
        $log->channel('channel')->info($this->message);
        $log->stack(['channel'], 'name')->info($this->message);

        try {
            $log->assertNotLogged('info', function ($message) {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The unexpected log with level [info] was logged in stack.'));
        }

        try {
            $log->channel('channel')->assertNotLogged('info', function ($message) {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The unexpected log with level [info] was logged in channel.'));
        }

        try {
            $log->stack(['channel'], 'name')->assertNotLogged('info', function ($message) {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The unexpected log with level [info] was logged in Stack:name.channel.'));
        }
    }

    public function testAssertNothingLogged()
    {
        $log = new LogFake;
        $log->info($this->message);
        $log->channel('channel')->info($this->message);
        $log->stack(['channel'], 'name')->info($this->message);

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

    public function testLogged()
    {
        $log = new LogFake;

        $this->assertTrue($log->logged('info')->isEmpty());
        $log->info($this->message);
        $this->assertFalse($log->logged('info')->isEmpty());

        $this->assertTrue($log->channel('channel')->logged('info')->isEmpty());
        $log->channel('channel')->info($this->message);
        $this->assertFalse($log->channel('channel')->logged('info')->isEmpty());

        $this->assertTrue($log->stack(['channel'], 'name')->logged('info')->isEmpty());
        $log->stack(['channel'], 'name')->info($this->message);
        $this->assertFalse($log->stack(['channel'], 'name')->logged('info')->isEmpty());
    }

    public function testLoggedWithCallback()
    {
        $log = new LogFake;

        $this->assertTrue($log->logged('info', function () {
            return true;
        })->isEmpty());
        $log->info($this->message);
        $this->assertFalse($log->logged('info', function () {
            return true;
        })->isEmpty());

        $this->assertTrue($log->channel('channel')->logged('info', function () {
            return true;
        })->isEmpty());
        $log->channel('channel')->info($this->message);
        $this->assertFalse($log->channel('channel')->logged('info', function () {
            return true;
        })->isEmpty());

        $this->assertTrue($log->stack(['channel'], 'name')->logged('info', function () {
            return true;
        })->isEmpty());
        $log->stack(['channel'], 'name')->info($this->message);
        $this->assertFalse($log->stack(['channel'], 'name')->logged('info', function () {
            return true;
        })->isEmpty());
    }

    public function testHasLogged()
    {
        $log = new LogFake;

        $this->assertFalse($log->hasLogged('info'));
        $log->info($this->message);
        $this->assertTrue($log->hasLogged('info'));

        $this->assertFalse($log->channel('channel')->hasLogged('info'));
        $log->channel('channel')->info($this->message);
        $this->assertTrue($log->channel('channel')->hasLogged('info'));

        $this->assertFalse($log->stack(['channel'], 'name')->hasLogged('info'));
        $log->stack(['channel'], 'name')->info($this->message);
        $this->assertTrue($log->stack(['channel'], 'name')->hasLogged('info'));
    }

    public function testHasNotLogged()
    {
        $log = new LogFake;

        $this->assertTrue($log->hasNotLogged('info'));
        $log->info($this->message);
        $this->assertFalse($log->hasNotLogged('info'));

        $this->assertTrue($log->channel('channel')->hasNotLogged('info'));
        $log->channel('channel')->info($this->message);
        $this->assertFalse($log->channel('channel')->hasNotLogged('info'));

        $this->assertTrue($log->stack(['channel'], 'name')->hasNotLogged('info'));
        $log->stack(['channel'], 'name')->info($this->message);
        $this->assertFalse($log->stack(['channel'], 'name')->hasNotLogged('info'));
    }

    public function testLoggingLevelMethods()
    {
        $log = new LogFake;

        $log->emergency('emergency log');
        $log->alert('alert log');
        $log->critical('critical log');
        $log->error('error log');
        $log->warning('warning log');
        $log->info('info log');
        $log->debug('debug log');
        $log->log('custom', 'custom log');
        $log->write('custom_2', 'custom log 2');

        $log->assertLogged('emergency', function ($message, $context) {
            return $message === 'emergency log';
        });
        $log->assertLogged('alert', function ($message, $context) {
            return $message === 'alert log';
        });
        $log->assertLogged('critical', function ($message, $context) {
            return $message === 'critical log';
        });
        $log->assertLogged('error', function ($message, $context) {
            return $message === 'error log';
        });
        $log->assertLogged('warning', function ($message, $context) {
            return $message === 'warning log';
        });
        $log->assertLogged('info', function ($message, $context) {
            return $message === 'info log';
        });
        $log->assertLogged('debug', function ($message, $context) {
            return $message === 'debug log';
        });
        $log->assertLogged('custom', function ($message, $context) {
            return $message === 'custom log';
        });
        $log->assertLogged('custom_2', function ($message, $context) {
            return $message === 'custom log 2';
        });
    }

    public function assertChannelAndDriverMethodsCanBeUsedInterchangably()
    {
        $log = new LogFake;

        $log->driver('channel')->info($this->message);
        $log->channel('channel')->assertLogged('info', function ($message) {
            return true;
        });
    }

    public function testCurrentStackIsTakenIntoAccount()
    {
        $log = new LogFake;

        $log->stack(['bugsnag', 'sentry'], 'dev_team')->info($this->message);

        $log->assertNotLogged('info');
        $log->stack(['bugsnag', 'sentry'], 'dev_team')->assertLogged('info');
    }

    public function testCanHaveStackChannelsInAnyOrder()
    {
        $log = new LogFake;

        $log->stack(['bugsnag', 'sentry'], 'dev_team')->info($this->message);

        $log->assertNotLogged('info');
        $log->stack(['sentry', 'bugsnag'], 'dev_team')->assertLogged('info');
    }

    public function testDifferentiatesBetweenStacksWithANameAndThoseWithout()
    {
        $log = new LogFake;

        $log->stack(['bugsnag', 'sentry'], 'dev_team')->info($this->message);
        $log->stack(['bugsnag', 'sentry'])->alert($this->message);

        $log->stack(['sentry', 'bugsnag'], 'dev_team')->assertNotLogged('alert');
        $log->stack(['sentry', 'bugsnag'])->assertNotLogged('info');
    }

    public function testDifferentiatesBetweenStacksAndChannelsWithTheSameName()
    {
        $log = new LogFake;

        $log->stack(['bugsnag', 'sentry'])->info($this->message);
        $log->channel('bugsnag.sentry')->alert($this->message);

        $log->stack(['bugsnag', 'sentry'])->assertNotLogged('alert');
        $log->channel('bugsnag.sentry')->assertNotLogged('info');

        $log->stack(['bugsnag', 'sentry'], 'name')->info($this->message);
        $log->channel('name.bugsnag.sentry')->alert($this->message);

        $log->stack(['name', 'bugsnag', 'sentry'])->assertNotLogged('alert');
        $log->channel('name.bugsnag.sentry')->assertNotLogged('info');
    }

    public function testAssertLoggedInStackDotNotatesSortedChannels()
    {
        $this->assertSame('Stack:name.a.b.c', (new LogFake)->stack(['c', 'b', 'a'], 'name')->currentChannel());
    }

    public function testClosuresProvideMessageAndContext()
    {
        $log = new LogFake;
        $log->info($this->message, ['key' => 'expected']);

        $items = $log->logged('info', function ($message, $context) {
            $this->assertSame(['key' => 'expected'], $context);
            return true;
        });
        $this->assertTrue($items->isNotEmpty());
        $log->assertLogged('info', function ($message, $context) {
            $this->assertSame(['key' => 'expected'], $context);
            return true;
        });
        $log->assertNotLogged('info', function ($message, $context) {
            $this->assertSame(['key' => 'expected'], $context);
            return false;
        });
    }
}
