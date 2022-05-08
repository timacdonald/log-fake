<?php

declare(strict_types=1);

namespace Tests;

use RuntimeException;
use TiMacDonald\CallableFake\CallableFake;
use TiMacDonald\Log\LogEntry;
use TiMacDonald\Log\LogFake;

/**
 * @small
 */
class AssertionTest extends TestCase
{
    public function testAssertLoggedFunc(): void
    {
        $log = new LogFake();

        // default...
        self::assertFailsWithMessage(
            fn () => $log->assertLogged(fn () => true),
            'Expected log was not created in the [stack] channel.'
        );
        self::assertFailsWithMessage(
            fn () => $log->assertLogged(fn () => false),
            'Expected log was not created in the [stack] channel.'
        );
        $log->info('xxxx');
        $log->assertLogged(fn () => true);
        self::assertFailsWithMessage(
            fn () => $log->assertLogged(fn () => false),
            'Expected log was not created in the [stack] channel.'
        );

        // channel...
        self::assertFailsWithMessage(
            fn () => $log->channel('channel')->assertLogged(fn () => true),
            'Expected log was not created in the [channel] channel.'
        );
        self::assertFailsWithMessage(
            fn () => $log->channel('channel')->assertLogged(fn () => false),
            'Expected log was not created in the [channel] channel.'
        );
        $log->channel('channel')->info('xxxx');
        $log->channel('channel')->assertLogged(fn () => true);
        self::assertFailsWithMessage(
            fn () => $log->channel('channel')->assertLogged(fn () => false),
            'Expected log was not created in the [channel] channel.'
        );

        // stack...
        self::assertFailsWithMessage(
            fn () => $log->stack(['c1', 'c2'], 'name')->assertLogged(fn () => true),
            'Expected log was not created in the [stack::name:c1,c2] channel.'
        );
        self::assertFailsWithMessage(
            fn () => $log->stack(['c1', 'c2'], 'name')->assertLogged(fn () => false),
            'Expected log was not created in the [stack::name:c1,c2] channel.'
        );
        $log->stack(['c1', 'c2'], 'name')->info('xxxx');
        $log->stack(['c1', 'c2'], 'name')->assertLogged(fn () => true);
        self::assertFailsWithMessage(
            fn () => $log->stack(['c1', 'c2'], 'name')->assertLogged(fn () => false),
            'Expected log was not created in the [stack::name:c1,c2] channel.'
        );
    }

    public function testAssertLoggedArgs(): void
    {
        $log = new LogFake();
        $callable = new CallableFake(fn () => true);
        $log->info('expected message', ['expected' => 'context']);

        $log->assertLogged($callable->asClosure());

        $callable->assertCalledTimes(function (LogEntry $log) {
            return $log->level === 'info' && $log->message === 'expected message' && $log->context === ['expected' => 'context'];
        }, 1);
    }

    public function testAssertLoggedCustomError(): void
    {
        $log = new LogFake();

        self::assertFailsWithMessage(
            fn () => $log->assertLogged(fn () => false, 'expected message'),
            'expected message'
        );
    }

    public function testAssertLoggedTimes(): void
    {
        $log = new LogFake();

        // default...
        $log->info('xxxx');
        self::assertFailsWithMessage(
            fn () => $log->assertLoggedTimes(fn () => true, 2),
            'Expected log was not created [2] times in the [stack] channel. Instead was created [1] times.'
        );
        self::assertFailsWithMessage(
            fn () => $log->assertLoggedTimes(fn () => false, 2),
            'Expected log was not created [2] times in the [stack] channel. Instead was created [0] times.'
        );
        $log->info('xxxx');
        $log->assertLoggedTimes(fn () => true, 2);
        self::assertFailsWithMessage(
            fn () => $log->assertLoggedTimes(fn () => false, 2),
            'Expected log was not created [2] times in the [stack] channel. Instead was created [0] times.'
        );

        // channel...
        $log->channel('channel')->assertLoggedTimes(fn () => true, 0);
        $log->channel('channel')->assertLoggedTimes(fn () => false, 0);
        $log->channel('channel')->info('xxxx');
        self::assertFailsWithMessage(
            fn () => $log->channel('channel')->assertLoggedTimes(fn () => true, 2),
            'Expected log was not created [2] times in the [channel] channel. Instead was created [1] times.'
        );
        self::assertFailsWithMessage(
            fn () => $log->channel('channel')->assertLoggedTimes(fn () => false, 2),
            'Expected log was not created [2] times in the [channel] channel. Instead was created [0] times.'
        );
        $log->channel('channel')->info('xxxx');
        $log->channel('channel')->assertLoggedTimes(fn () => true, 2);
        self::assertFailsWithMessage(
            fn () => $log->channel('channel')->assertLoggedTimes(fn () => false, 2),
            'Expected log was not created [2] times in the [channel] channel. Instead was created [0] times.'
        );

        // stack...
        $log->stack(['c1', 'c2'], 'name')->assertLoggedTimes(fn () => true, 0);
        $log->stack(['c1', 'c2'], 'name')->assertLoggedTimes(fn () => false, 0);
        $log->stack(['c1', 'c2'], 'name')->info('xxxx');
        self::assertFailsWithMessage(
            fn () => $log->stack(['c1', 'c2'], 'name')->assertLoggedTimes(fn () => true, 2),
            'Expected log was not created [2] times in the [stack::name:c1,c2] channel. Instead was created [1] times.'
        );
        self::assertFailsWithMessage(
            fn () => $log->stack(['c1', 'c2'], 'name')->assertLoggedTimes(fn () => false, 2),
            'Expected log was not created [2] times in the [stack::name:c1,c2] channel. Instead was created [0] times.'
        );
        $log->stack(['c1', 'c2'], 'name')->info('xxxx');
        $log->stack(['c1', 'c2'], 'name')->assertLoggedTimes(fn () => true, 2);
        self::assertFailsWithMessage(
            fn () => $log->stack(['c1', 'c2'], 'name')->assertLoggedTimes(fn () => false, 2),
            'Expected log was not created [2] times in the [stack::name:c1,c2] channel. Instead was created [0] times.'
        );
    }

    public function testAssertLoggedTimesArgs(): void
    {
        $log = new LogFake();
        $callable = new CallableFake(fn () => true);
        $log->info('expected message', ['expected' => 'context']);

        $log->assertLoggedTimes($callable->asClosure(), 1);

        $callable->assertCalledTimes(function (LogEntry $log) {
            return $log->level === 'info' && $log->message === 'expected message' && $log->context === ['expected' => 'context'];
        }, 1);
    }

    public function testAssertLoggedTimesCustomError(): void
    {
        $log = new LogFake();

        self::assertFailsWithMessage(
            fn () => $log->assertLoggedTimes(fn () => false, 3, 'expected message'),
            'expected message'
        );
    }

    public function testAssertNotLogged(): void
    {
        $log = new LogFake();

        // default channel...
        $log->assertNotLogged(fn () => true);
        $log->assertNotLogged(fn () => false);
        $log->info('xxxx');
        self::assertFailsWithMessage(
            fn () => $log->assertNotLogged(fn () => true),
            'Expected log was not created [0] times in the [stack] channel. Instead was created [1] times.'
        );
        $log->assertNotLogged(fn () => false);

        // channel...
        $log->channel('channel')->assertNotLogged(fn () => true);
        $log->channel('channel')->assertNotLogged(fn () => false);
        $log->channel('channel')->info('xxxx');
        self::assertFailsWithMessage(
            fn () => $log->channel('channel')->assertNotLogged(fn () => true),
            'Expected log was not created [0] times in the [channel] channel. Instead was created [1] times.'
        );
        $log->channel('channel')->assertNotLogged(fn () => false);

        // stack...
        $log->stack(['c1', 'c2'], 'name')->assertNotLogged(fn () => true);
        $log->stack(['c1', 'c2'], 'name')->assertNotLogged(fn () => false);
        $log->stack(['c1', 'c2'], 'name')->info('xxxx');
        self::assertFailsWithMessage(
            fn () => $log->stack(['c1', 'c2'], 'name')->assertNotLogged(fn () => true),
            'Expected log was not created [0] times in the [stack::name:c1,c2] channel. Instead was created [1] times.'
        );
        $log->stack(['c1', 'c2'], 'name')->assertNotLogged(fn () => false);
    }

    public function testAssertNotLoggedArgs(): void
    {
        $log = new LogFake();
        $callable = new CallableFake(fn () => false);
        $log->info('expected message', ['expected' => 'context']);

        $log->assertNotLogged($callable->asClosure());

        $callable->assertCalledTimes(function (LogEntry $log) {
            return $log->level === 'info' && $log->message === 'expected message' && $log->context === ['expected' => 'context'];
        }, 1);
    }

    public function testAssertNotLoggedCustomMessage(): void
    {
        $log = new LogFake();
        $log->info('xxxx');

        self::assertFailsWithMessage(
            fn () => $log->assertNotLogged(fn () => true, 'expected message'),
            'expected message'
        );
    }

    public function testAssertNothingLogged(): void
    {
        $log = new LogFake();

        // default channel...
        $log->assertNothingLogged();
        $log->info('xxxx');
        self::assertFailsWithMessage(
            fn () => $log->assertNothingLogged(),
            'Expected [0] logs to be created in the [stack] channel. Found [1] instead.'
        );

        // channel...
        $log->channel('channel')->assertNothingLogged();
        $log->channel('channel')->info('expected message');
        self::assertFailsWithMessage(
            fn () => $log->channel('channel')->assertNothingLogged(),
            'Expected [0] logs to be created in the [channel] channel. Found [1] instead.'
        );

        // stack...
        $log->stack(['c1', 'c2'], 'name')->assertNothingLogged();
        $log->stack(['c1', 'c2'], 'name')->info('xxxx');
        self::assertFailsWithMessage(
            fn () => $log->stack(['c1', 'c2'], 'name')->assertNothingLogged(),
            'Expected [0] logs to be created in the [stack::name:c1,c2] channel. Found [1] instead.'
        );
    }

    public function testAssertNothingLoggedCustomError(): void
    {
        $log = new LogFake();
        $log->info('xxxx');

        self::assertFailsWithMessage(
            fn () => $log->assertNothingLogged('expected message'),
            'expected message'
        );
    }

    public function testAssertWasForgotten(): void
    {
        $log = new LogFake();

        // default channel...
        self::assertFailsWithMessage(
            fn () => $log->assertWasForgotten(),
            'Expected the [stack] channel to be forgotten at least once. It was forgotten [0] times.'
        );
        $log->forgetChannel('stack');
        $log->assertWasForgotten();

        // channel...
        self::assertFailsWithMessage(
            fn () => $log->channel('channel')->assertWasForgotten(),
            'Expected the [channel] channel to be forgotten at least once. It was forgotten [0] times.',
        );
        $log->forgetChannel('channel');
        $log->channel('channel')->assertWasForgotten();

        // not available on a stack.
        self::assertFailsWithMessage(
            fn () => $log->stack(['c1'])->assertWasForgotten(),
            'Cannot call [Log::stack(...)->assertWasForgotten(...)] as stack is not able to be forgotten.',
        );
    }

    public function testAssertWasForgottenCustomError(): void
    {
        $log = new LogFake();

        self::assertFailsWithMessage(
            fn () => $log->assertWasForgotten('expected message'),
            'expected message'
        );
    }

    public function testAssertWasForgottenTimes(): void
    {
        $log = new LogFake();

        // default channel...
        self::assertFailsWithMessage(
            fn () => $log->assertWasForgottenTimes(2),
            'Expected the [stack] channel to be forgotten [2] times. It was forgotten [0] times.'
        );
        $log->forgetChannel('stack');
        $log->forgetChannel('stack');
        $log->assertWasForgottenTimes(2);

        self::assertFailsWithMessage(
            fn () => $log->channel('channel')->assertWasForgottenTimes(2),
            'Expected the [channel] channel to be forgotten [2] times. It was forgotten [0] times.'
        );
        $log->forgetChannel('channel');
        $log->forgetChannel('channel');
        $log->channel('channel')->assertWasForgottenTimes(2);

        // not available on a stack.
        self::assertFailsWithMessage(
            fn () => $log->stack(['c1'])->assertWasForgottenTimes(2),
            'Cannot call [Log::stack(...)->assertWasForgottenTimes(...)] as stack is not able to be forgotten.',
        );
    }

    public function testAssertWasForgottenTimesCustomError(): void
    {
        $log = new LogFake();

        self::assertFailsWithMessage(
            fn () => $log->assertWasForgottenTimes(3, 'expected message'),
            'expected message'
        );
    }

    public function testAssertWasNotForgotten(): void
    {
        $log = new LogFake();

        // default channel...
        $log->assertWasNotForgotten();
        $log->forgetChannel('stack');
        self::assertFailsWithMessage(
            fn () => $log->assertWasNotForgotten(),
            'Expected the [stack] channel to be forgotten [0] times. It was forgotten [1] times.'
        );

        // channel...
        $log->channel('channel')->assertWasNotForgotten();
        $log->forgetChannel('channel');
        self::assertFailsWithMessage(
            fn () => $log->channel('channel')->assertWasNotForgotten(),
            'Expected the [channel] channel to be forgotten [0] times. It was forgotten [1] times.'
        );

        // not available on a stack.
        self::assertFailsWithMessage(
            fn () => $log->stack(['c1'])->assertWasNotForgotten(),
            'Cannot call [Log::stack(...)->assertWasNotForgotten(...)] as stack is not able to be forgotten.',
        );
    }

    public function testAssertWasNotForgottenCustomError(): void
    {
        $log = new LogFake();
        $log->forgetChannel('stack');

        self::assertFailsWithMessage(
            fn () => $log->assertWasNotForgotten('expected message'),
            'expected message'
        );
    }

    public function testAssertChannelIsCurrentlyForgotten(): void
    {
        $log = new LogFake();

        self::assertFailsWithMessage(
            fn () => $log->assertChannelIsCurrentlyForgotten('channel'),
            'Expected to find the [channel] channel to be forgotten. It was not.'
        );
        $log->channel('channel')->info('xxxx');
        self::assertFailsWithMessage(
            fn () => $log->assertChannelIsCurrentlyForgotten('channel'),
            'Expected to find the [channel] channel to be forgotten. It was not.'
        );
        $log->forgetChannel('channel');
        $log->assertChannelIsCurrentlyForgotten('channel');
    }

    public function testAssertChannelIsCurrentlyForgottenCustomMessage(): void
    {
        $log = new LogFake();
        $log->channel('channel');

        self::assertFailsWithMessage(
            fn () => $log->assertChannelIsCurrentlyForgotten('channel', 'expected message'),
            'expected message'
        );
    }

    public function testAssertCurrentContext(): void
    {
        $log = new LogFake();

        // default channel...
        $log->assertCurrentContext([]);
        self::assertFailsWithMessage(
            fn () => $log->assertCurrentContext(['foo' => 'bar']),
            'Expected to find the context [{"foo":"bar"}] in the [stack] channel. Found [{}] instead.'
        );
        $log->withContext(['foo' => 'bar']);
        self::assertFailsWithMessage(
            fn () => $log->assertCurrentContext([]),
            'Expected to find the context [[]] in the [stack] channel. Found [{"foo":"bar"}] instead.'
        );
        $log->assertCurrentContext(['foo' => 'bar']);

        // channel...
        $log->channel('channel')->assertCurrentContext([]);
        self::assertFailsWithMessage(
            fn () => $log->channel('channel')->assertCurrentContext(['foo' => 'bar']),
            'Expected to find the context [{"foo":"bar"}] in the [channel] channel. Found [{}] instead.'
        );
        $log->channel('channel')->withContext(['foo' => 'bar']);
        self::assertFailsWithMessage(
            fn () => $log->channel('channel')->assertCurrentContext([]),
            'Expected to find the context [[]] in the [channel] channel. Found [{"foo":"bar"}] instead.'
        );
        $log->channel('channel')->assertCurrentContext(['foo' => 'bar']);

        // not available on a stack.
        self::assertFailsWithMessage(
            fn () => $log->stack(['c1'])->assertCurrentContext([]),
            'Cannot call [Log::stack(...)->assertCurrentContext(...)] as stack contexts are reset each time they are resolved from the LogManager.',
        );
    }

    public function testAssertCurrentContextCustomMesage(): void
    {
        $log = new LogFake();

        self::assertFailsWithMessage(
            fn () => $log->assertCurrentContext(fn () => false, 'expected message'),
            'expected message'
        );
    }

    public function testAssertCurrentContextWithClosure(): void
    {
        $log = new LogFake();

        // default channel...
        $log->assertCurrentContext(fn () => true);
        self::assertFailsWithMessage(
            fn () => $log->assertCurrentContext(fn () => false),
            'Unexpected context found in the [stack] channel. Found [{}].'
        );
        $log->withContext(['foo' => 'bar']);
        $log->assertCurrentContext(fn () => true);
        self::assertFailsWithMessage(
            fn () => $log->assertCurrentContext(fn () => false),
            'Unexpected context found in the [stack] channel. Found [{"foo":"bar"}].'
        );

        // channel...
        $log->channel('channel')->assertCurrentContext(fn () => true);
        self::assertFailsWithMessage(
            fn () => $log->channel('channel')->assertCurrentContext(fn () => false),
            'Unexpected context found in the [channel] channel. Found [{}].'
        );
        $log->channel('channel')->withContext(['foo' => 'bar']);
        $log->channel('channel')->assertCurrentContext(fn () => true);
        self::assertFailsWithMessage(
            fn () => $log->channel('channel')->assertCurrentContext(fn () => false),
            'Unexpected context found in the [channel] channel. Found [{"foo":"bar"}].'
        );
    }

    public function testAssertCurrentContextWithClosureArgs(): void
    {
        $log = new LogFake();
        $callable = new CallableFake(fn () => true);

        $log->withContext(['foo' => 'bar']);
        $log->assertCurrentContext($callable->asClosure());
        $log->withContext(['bar' => 'baz']);
        $log->assertCurrentContext($callable->asClosure());

        $callable->assertCalledTimes(function (array $context) {
            return $context === ['foo' => 'bar'];
        }, 1);
        $callable->assertCalledTimes(function (array $context) {
            return $context === ['foo' => 'bar', 'bar' => 'baz'];
        }, 1);
    }

    public function testItCannotAssertCurrentContextForStacks(): void
    {
        $log = new LogFake();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot call [Log::stack(...)->assertCurrentContext(...)] as stack contexts are reset each time they are resolved from the LogManager.');

        $log->stack(['c1', 'c2'], 'name')->assertCurrentContext(['foo' => 'bar']);
    }

    public function testAssertCurrentContextWithNonBoolReturnedFromClosure(): void
    {
        $log = new LogFake();

        $log->assertCurrentContext(fn () => 1); /** @phpstan-ignore-line */
        self::assertFailsWithMessage(
            fn () => $log->assertCurrentContext(fn () => 0), /** @phpstan-ignore-line */
            'Unexpected context found in the [stack] channel. Found [{}].'
        );
    }

    public function testAssertLoggedFuncWithNonBoolReturnedFromClosure(): void
    {
        $log = new LogFake();
        $log->info('xxxx');

        $log->assertLogged(fn () => 1); /** @phpstan-ignore-line */
        self::assertFailsWithMessage(
            fn () => $log->assertLogged(fn () => 0), /** @phpstan-ignore-line */
            'Expected log was not created in the [stack] channel.'
        );
    }
}
