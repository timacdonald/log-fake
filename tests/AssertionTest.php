<?php

namespace Tests;

use RuntimeException;
use TiMacDonald\CallableFake\CallableFake;
use TiMacDonald\Log\LogEntry;
use TiMacDonald\Log\LogFake;

class AssertionTest extends TestCase
{
    public function test_assert_logged_func(): void
    {
        $log = new LogFake;

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

    public function test_assert_logged_args(): void
    {
        $log = new LogFake;
        $callable = new CallableFake(fn () => true);
        $log->info('expected message', ['expected' => 'context']);

        $log->assertLogged($callable->asClosure());

        $callable->assertCalledTimes(function (LogEntry $log) {
            return $log->level === 'info' && $log->message === 'expected message' && $log->context === ['expected' => 'context'];
        }, 1);
    }

    public function test_assert_logged_custom_error(): void
    {
        $log = new LogFake;

        self::assertFailsWithMessage(
            fn () => $log->assertLogged(fn () => false, 'expected message'),
            'expected message'
        );
    }

    public function test_assert_logged_times(): void
    {
        $log = new LogFake;

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

    public function test_assert_logged_times_args(): void
    {
        $log = new LogFake;
        $callable = new CallableFake(fn () => true);
        $log->info('expected message', ['expected' => 'context']);

        $log->assertLoggedTimes($callable->asClosure(), 1);

        $callable->assertCalledTimes(function (LogEntry $log) {
            return $log->level === 'info' && $log->message === 'expected message' && $log->context === ['expected' => 'context'];
        }, 1);
    }

    public function test_assert_logged_times_custom_error(): void
    {
        $log = new LogFake;

        self::assertFailsWithMessage(
            fn () => $log->assertLoggedTimes(fn () => false, 3, 'expected message'),
            'expected message'
        );
    }

    public function test_assert_not_logged(): void
    {
        $log = new LogFake;

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

    public function test_assert_not_logged_args(): void
    {
        $log = new LogFake;
        $callable = new CallableFake(fn () => false);
        $log->info('expected message', ['expected' => 'context']);

        $log->assertNotLogged($callable->asClosure());

        $callable->assertCalledTimes(function (LogEntry $log) {
            return $log->level === 'info' && $log->message === 'expected message' && $log->context === ['expected' => 'context'];
        }, 1);
    }

    public function test_assert_not_logged_custom_message(): void
    {
        $log = new LogFake;
        $log->info('xxxx');

        self::assertFailsWithMessage(
            fn () => $log->assertNotLogged(fn () => true, 'expected message'),
            'expected message'
        );
    }

    public function test_assert_nothing_logged(): void
    {
        $log = new LogFake;

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

    public function test_assert_nothing_logged_custom_error(): void
    {
        $log = new LogFake;
        $log->info('xxxx');

        self::assertFailsWithMessage(
            fn () => $log->assertNothingLogged('expected message'),
            'expected message'
        );
    }

    public function test_assert_was_forgotten(): void
    {
        $log = new LogFake;

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

    public function test_assert_was_forgotten_custom_error(): void
    {
        $log = new LogFake;

        self::assertFailsWithMessage(
            fn () => $log->assertWasForgotten('expected message'),
            'expected message'
        );
    }

    public function test_assert_was_forgotten_times(): void
    {
        $log = new LogFake;

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

    public function test_assert_was_forgotten_times_custom_error(): void
    {
        $log = new LogFake;

        self::assertFailsWithMessage(
            fn () => $log->assertWasForgottenTimes(3, 'expected message'),
            'expected message'
        );
    }

    public function test_assert_was_not_forgotten(): void
    {
        $log = new LogFake;

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

    public function test_assert_was_not_forgotten_custom_error(): void
    {
        $log = new LogFake;
        $log->forgetChannel('stack');

        self::assertFailsWithMessage(
            fn () => $log->assertWasNotForgotten('expected message'),
            'expected message'
        );
    }

    public function test_assert_channel_is_currently_forgotten(): void
    {
        $log = new LogFake;

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

    public function test_assert_channel_is_currently_forgotten_custom_message(): void
    {
        $log = new LogFake;
        $log->channel('channel');

        self::assertFailsWithMessage(
            fn () => $log->assertChannelIsCurrentlyForgotten('channel', 'expected message'),
            'expected message'
        );
    }

    public function test_assert_current_context(): void
    {
        $log = new LogFake;

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

    public function test_assert_current_context_custom_mesage(): void
    {
        $log = new LogFake;

        self::assertFailsWithMessage(
            fn () => $log->assertCurrentContext(fn () => false, 'expected message'),
            'expected message'
        );
    }

    public function test_assert_current_context_with_closure(): void
    {
        $log = new LogFake;

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

    public function test_assert_current_context_with_closure_args(): void
    {
        $log = new LogFake;
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

    public function test_it_cannot_assert_current_context_for_stacks(): void
    {
        $log = new LogFake;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot call [Log::stack(...)->assertCurrentContext(...)] as stack contexts are reset each time they are resolved from the LogManager.');

        $log->stack(['c1', 'c2'], 'name')->assertCurrentContext(['foo' => 'bar']);
    }

    public function test_assert_current_context_with_non_bool_returned_from_closure(): void
    {
        $log = new LogFake;

        /** @phpstan-ignore argument.type */
        $log->assertCurrentContext(fn () => 1);
        self::assertFailsWithMessage(
            /** @phpstan-ignore argument.type */
            fn () => $log->assertCurrentContext(fn () => 0),
            'Unexpected context found in the [stack] channel. Found [{}].'
        );
    }

    public function test_assert_logged_func_with_non_bool_returned_from_closure(): void
    {
        $log = new LogFake;
        $log->info('xxxx');

        /** @phpstan-ignore argument.type */
        $log->assertLogged(fn () => 1);
        self::assertFailsWithMessage(
            /** @phpstan-ignore argument.type */
            fn () => $log->assertLogged(fn () => 0),
            'Expected log was not created in the [stack] channel.'
        );
    }

    public function test_assert_has_shared_context(): void
    {
        $log = new LogFake;
        $log->shareContext(['shared' => 'context']);

        $log->assertHasSharedContext(fn ($context) => $context === ['shared' => 'context']);
        self::assertFailsWithMessage(
            fn () => $log->assertHasSharedContext(fn ($context) => false),
            'Expected shared context was not found.'
        );
    }

    public function test_it_can_provide_custom_message_with_assert_has_shared_context(): void
    {
        $log = new LogFake;
        $log->shareContext(['shared' => 'context']);

        self::assertFailsWithMessage(
            fn () => $log->assertHasSharedContext(fn ($context) => false, 'Whoops!'),
            'Whoops!'
        );
    }

    public function test_it_can_pass_context_array_directly_to_assert_has_shared_context(): void
    {
        $log = new LogFake;
        $log->shareContext(['shared' => 'context']);

        $log->assertHasSharedContext(['shared' => 'context']);
        self::assertFailsWithMessage(
            fn () => $log->assertHasSharedContext([]),
            'Expected shared context was not found.'
        );

    }
}
