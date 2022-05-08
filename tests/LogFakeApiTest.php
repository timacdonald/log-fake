<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\ExpectationFailedException;
use Stringable;
use Symfony\Component\VarDumper\VarDumper;
use Throwable;
use TiMacDonald\CallableFake\CallableFake;
use TiMacDonald\Log\ChannelFake;
use TiMacDonald\Log\LogEntry;
use TiMacDonald\Log\LogFake;

/**
 * @small
 */
class LogFakeApiTest extends TestCase
{
    public function testLoggingLevelMethods(): void
    {
        $log = new LogFake();

        // default channel...
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

        $log->assertLogged(fn (LogEntry $log): bool => $log->level === 'emergency');
        $log->assertLogged(fn (LogEntry $log): bool => $log->level === 'alert');
        $log->assertLogged(fn (LogEntry $log): bool => $log->level === 'critical');
        $log->assertLogged(fn (LogEntry $log): bool => $log->level === 'error');
        $log->assertLogged(fn (LogEntry $log): bool => $log->level === 'warning');
        $log->assertLogged(fn (LogEntry $log): bool => $log->level === 'info');
        $log->assertLogged(fn (LogEntry $log): bool => $log->level === 'notice');
        $log->assertLogged(fn (LogEntry $log): bool => $log->level === 'debug');
        $log->assertLogged(fn (LogEntry $log): bool => $log->level === 'custom');
        $log->assertLogged(fn (LogEntry $log): bool => $log->level === 'custom_2');

        // channel...
        $log->channel('channel')->emergency('emergency log');
        $log->channel('channel')->alert('alert log');
        $log->channel('channel')->critical('critical log');
        $log->channel('channel')->error('error log');
        $log->channel('channel')->warning('warning log');
        $log->channel('channel')->info('info log');
        $log->channel('channel')->notice('notice log');
        $log->channel('channel')->debug('debug log');
        $log->channel('channel')->log('custom', 'custom log');
        $log->channel('channel')->write('custom_2', 'custom log 2');

        $log->channel('channel')->assertLogged(fn (LogEntry $log): bool => $log->level === 'emergency');
        $log->channel('channel')->assertLogged(fn (LogEntry $log): bool => $log->level === 'alert');
        $log->channel('channel')->assertLogged(fn (LogEntry $log): bool => $log->level === 'critical');
        $log->channel('channel')->assertLogged(fn (LogEntry $log): bool => $log->level === 'error');
        $log->channel('channel')->assertLogged(fn (LogEntry $log): bool => $log->level === 'warning');
        $log->channel('channel')->assertLogged(fn (LogEntry $log): bool => $log->level === 'info');
        $log->channel('channel')->assertLogged(fn (LogEntry $log): bool => $log->level === 'notice');
        $log->channel('channel')->assertLogged(fn (LogEntry $log): bool => $log->level === 'debug');
        $log->channel('channel')->assertLogged(fn (LogEntry $log): bool => $log->level === 'custom');
        $log->channel('channel')->assertLogged(fn (LogEntry $log): bool => $log->level === 'custom_2');

        // stack...
        $log->stack(['c1', 'c2'], 'name')->emergency('emergency log');
        $log->stack(['c1', 'c2'], 'name')->alert('alert log');
        $log->stack(['c1', 'c2'], 'name')->critical('critical log');
        $log->stack(['c1', 'c2'], 'name')->error('error log');
        $log->stack(['c1', 'c2'], 'name')->warning('warning log');
        $log->stack(['c1', 'c2'], 'name')->info('info log');
        $log->stack(['c1', 'c2'], 'name')->notice('notice log');
        $log->stack(['c1', 'c2'], 'name')->debug('debug log');
        $log->stack(['c1', 'c2'], 'name')->log('custom', 'custom log');
        $log->stack(['c1', 'c2'], 'name')->write('custom_2', 'custom log 2');

        $log->stack(['c1', 'c2'], 'name')->assertLogged(fn (LogEntry $log): bool => $log->level === 'emergency');
        $log->stack(['c1', 'c2'], 'name')->assertLogged(fn (LogEntry $log): bool => $log->level === 'alert');
        $log->stack(['c1', 'c2'], 'name')->assertLogged(fn (LogEntry $log): bool => $log->level === 'critical');
        $log->stack(['c1', 'c2'], 'name')->assertLogged(fn (LogEntry $log): bool => $log->level === 'error');
        $log->stack(['c1', 'c2'], 'name')->assertLogged(fn (LogEntry $log): bool => $log->level === 'warning');
        $log->stack(['c1', 'c2'], 'name')->assertLogged(fn (LogEntry $log): bool => $log->level === 'info');
        $log->stack(['c1', 'c2'], 'name')->assertLogged(fn (LogEntry $log): bool => $log->level === 'notice');
        $log->stack(['c1', 'c2'], 'name')->assertLogged(fn (LogEntry $log): bool => $log->level === 'debug');
        $log->stack(['c1', 'c2'], 'name')->assertLogged(fn (LogEntry $log): bool => $log->level === 'custom');
        $log->stack(['c1', 'c2'], 'name')->assertLogged(fn (LogEntry $log): bool => $log->level === 'custom_2');
    }

    public function testAssertChannelAndDriverMethodsCanBeUsedInterchangably(): void
    {
        $log = new LogFake();

        $log->driver('channel')->info('expected message');

        $log->channel('channel')->assertLogged(fn (): bool => true);
    }

    public function testCurrentStackIsTakenIntoAccount(): void
    {
        $log = new LogFake();

        $log->stack(['bugsnag', 'sentry'], 'dev_team')->info('expected message');

        $log->assertNotLogged(fn () => true);
        $log->stack(['bugsnag', 'sentry'], 'dev_team')->assertLogged(fn () => true);
    }

    public function testCanHaveStackChannelsInAnyOrder(): void
    {
        $log = new LogFake();

        $log->stack(['bugsnag', 'sentry'], 'dev_team')->info('expected message');

        $log->stack(['sentry', 'bugsnag'], 'dev_team')->assertLogged(fn () => true);
    }

    public function testItDifferentiatesBetweenStacksWithANameAndThoseWithout(): void
    {
        $log = new LogFake();

        $log->stack(['bugsnag', 'sentry'], 'dev_team')->info('expected message');
        $log->stack(['bugsnag', 'sentry'])->alert('expected message');

        $log->stack(['sentry', 'bugsnag'], 'dev_team')->assertNotLogged(fn (LogEntry $log) => $log->level === 'alert');
        $log->stack(['sentry', 'bugsnag'])->assertNotLogged(fn (LogEntry $log) => $log->level === 'info');
    }

    // up to here...

    public function testDifferentiatesBetweenStacksAndChannelsWithTheSameName(): void
    {
        $log = new LogFake();

        $log->stack(['bugsnag', 'sentry'])->info('expected message');
        $log->channel('bugsnag,sentry')->alert('expected message');

        $log->stack(['bugsnag', 'sentry'])->assertNotLogged(fn (LogEntry $log) => $log->level === 'alert');
        $log->channel('bugsnag,sentry')->assertNotLogged(fn (LogEntry $log) => $log->level === 'info');

        $log->stack(['bugsnag', 'sentry'], 'name')->info('expected message');
        $log->channel('name:bugsnag,sentry')->alert('expected message');

        $log->stack(['name', 'bugsnag', 'sentry'])->assertNotLogged(fn (LogEntry $log) => $log->level === 'alert');
        $log->channel('name:bugsnag,sentry')->assertNotLogged(fn (LogEntry $log) => $log->level === 'info');
    }

    public function testAssertLoggedInStackDotNotatesSortedChannels(): void
    {
        $log = new LogFake();

        try {
            $log->stack(['c', 'b', 'a'], 'name')->assertLogged(fn () => false);
            self::fail();
        } catch (ExpectationFailedException $e) {
            self::assertStringContainsString('Expected log was not created in the [stack::name:a,b,c] channel.', $e->getMessage());
        }
    }

    public function testSetDefaultDriver(): void
    {
        $log = new LogFake();
        $log->setDefaultDriver('expected-driver');

        self::assertSame('expected-driver', Config::get('logging.default'));
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
            /** @phpstan-ignore-next-line */
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

            /** @phpstan-ignore-next-line */
            public function until($event, $payload = [])
            {
                return null;
            }

            /** @phpstan-ignore-next-line */
            public function dispatch($event, $payload = [], $halt = false)
            {
                return null;
            }

            /** @phpstan-ignore-next-line */
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
        self::assertSame($log->getLogger(), $log->channel());
    }

    public function testItCanDumpDefaultChannel(): void
    {
        $log = new LogFake();
        $dumps = [];
        VarDumper::setHandler(static function (array $logs) use (&$dumps) {
            $dumps[] = $logs;
        });

        $log->info('expected log 1');
        $log->debug('expected log 2');
        $log->channel('channel')->info('missing channel log');
        $log = $log->dump();

        self::assertInstanceOf(ChannelFake::class, $log);
        self::assertCount(1, $dumps);
        $logs = $dumps[0];

        self::assertCount(2, $logs);

        self::assertSame([
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
            $dumps[] = $logs;
        });
        $log->info('expected log');
        $log->debug('missing log');
        $log->channel('channel')->info('missing channel log');
        $log = $log->dump(fn (LogEntry $log) => $log->level === 'info');

        self::assertInstanceOf(ChannelFake::class, $log);
        self::assertCount(1, $dumps);
        $logs = $dumps[0];
        self::assertCount(1, $logs);
        self::assertSame([
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
            $dumps[] = $logs;
        });
        $log->info('missing log');
        $log->channel('unknown')->info('missing log');
        $log->channel('known')->info('expected log 1');
        $log->channel('known')->debug('expected log 2');
        $log = $log->channel('known')->dump();

        self::assertInstanceOf(ChannelFake::class, $log);
        self::assertCount(1, $dumps);
        $logs = $dumps[0];
        self::assertCount(2, $logs);
        self::assertSame([
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
            $dumps[] = $logs;
        });
        $log->info('missing log');
        $log->channel('unknown')->info('missing log');
        $log->channel('known')->info('expected log');
        $log->channel('known')->debug('missing log');
        $log = $log->channel('known')->dump(fn (LogEntry $log) => $log->level === 'info');

        self::assertInstanceOf(ChannelFake::class, $log);
        self::assertCount(1, $dumps);
        $logs = $dumps[0];
        self::assertCount(1, $logs);
        self::assertSame([
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
            $dumps[] = $logs;
        });

        $log->info('expected log 1');
        $log->debug('expected log 2');
        $log->channel('channel')->info('expected log 3');
        $log->channel('channel')->debug('expected log 4');
        $log = $log->dumpAll();

        self::assertInstanceOf(LogFake::class, $log);
        self::assertCount(1, $dumps);
        $logs = $dumps[0];

        self::assertCount(4, $logs);

        self::assertSame([
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
            $dumps[] = $logs;
        });

        $log->info('expected log 1');
        $log->debug('missing log');
        $log->channel('channel')->info('expected log 2');
        $log->channel('channel')->debug('missing log');
        $log = $log->dumpAll(fn (LogEntry $log) => $log->level === 'info');

        self::assertInstanceOf(LogFake::class, $log);
        self::assertCount(1, $dumps);
        $logs = $dumps[0];

        self::assertCount(2, $logs);

        self::assertSame([
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
        Config::set('logging.default', null);

        $log->info('xxxx');
        $log->channel('null')->assertLogged(fn () => true);
    }

    public function testItCanLogStringableObjects(): void
    {
        $log = new LogFake();
        $callable = new CallableFake(fn () => true);
        $log->info(new class () implements Stringable {
            public function __toString(): string
            {
                return 'expected message';
            }
        });
        $log->assertLogged($callable->asClosure());

        $callable->assertCalledTimes(function (LogEntry $log) {
            return $log->message instanceof Stringable && $log->message->__toString() === 'expected message';
        }, 1);
    }

    public function testItAddsContextToLogs(): void
    {
        $log = new LogFake();
        $callable = new CallableFake(fn () => true);

        $log->withContext(['foo' => 'xxxx'])
            ->withContext(['bar' => 'xxxx'])
            ->info('expected message', [
                'baz' => 'xxxx',
            ]);
        $log->assertLogged($callable->asClosure());

        $callable->assertCalledTimes(function (LogEntry $log) {
            return $log->context === [
                'foo' => 'xxxx',
                'bar' => 'xxxx',
                'baz' => 'xxxx',
            ];
        }, 1);
    }

    public function testItCanClearContext(): void
    {
        $log = new LogFake();
        $callable = new CallableFake(fn () => true);

        $log->withContext(['foo' => 'xxxx'])
            ->withoutContext()
            ->info('expected message', [
                'baz' => 'xxxx',
            ]);
        $log->assertLogged($callable->asClosure());

        $callable->assertCalledTimes(function (LogEntry $log) {
            return $log->context === [
                'baz' => 'xxxx',
            ];
        }, 1);
    }


    public function testItCanFakeOnDemandChannels(): void
    {
        $log = new LogFake();

        $log->build([])->info('expected message 1');
        $log->channel('ondemand::{}')->assertLogged(fn (LogEntry $log) => $log->message === 'expected message 1');

        $log->build(['foo' => 'bar'])->info('expected message 2');
        $log->channel('ondemand::{"foo":"bar"}')->assertLogged(fn (LogEntry $log) => $log->message === 'expected message 2');
    }

    public function testItCanRetrieveChannels(): void
    {
        $log = new LogFake();

        $channel = $log->channel('expected-channel');

        self::assertSame(['expected-channel' => $channel], $log->getChannels());
    }

    public function testItCanBindItselfToTheContainer(): void
    {
        self::assertNotInstanceOf(LogFake::class, Log::getFacadeRoot());

        $log = LogFake::bind();

        self::assertSame($log, Log::getFacadeRoot());
    }

    public function testItResetsStackContextOnChannelBuild(): void
    {
        $log = new LogFake();

        $stack1 = $log->stack(['c1'], 'name');
        $stack1->withContext(['bound' => 'context']);
        $stack1->info('message 1', ['logged' => 'context']);
        $stack1->assertLogged(function (LogEntry $log) {
            return $log->message === 'message 1'
                && $log->context === ['bound' => 'context', 'logged' => 'context'];
        });

        $stack2 = $log->stack(['c1'], 'name');
        $stack2->info('message 2', ['logged' => 'context']);
        $stack2->assertLogged(function (LogEntry $log) {
            return $log->message === 'message 2'
                && $log->context === ['logged' => 'context'];
        });
    }

    public function testItGivesStacksANameWhenNoneIsProvided(): void
    {
        $log = new LogFake();

        try {
            $log->stack(['c1'])->assertLogged(fn () => true);
            self::fail();
        } catch (Throwable $e) {
            self::assertStringContainsString('Expected log was not created in the [stack::unnamed:c1] channel.', $e->getMessage());
        }
    }

    public function testItClearsContextWhenAChannelIsForgotten(): void
    {
        $log = new LogFake();
        $log->channel('channel')->withContext(['foo' => 'bar']);
        $log->forgetChannel('channel');
        $log->channel('channel')->info('expected message');

        $log->channel('channel')->assertLogged(fn (LogEntry $log) => $log->context === []);
    }
}
