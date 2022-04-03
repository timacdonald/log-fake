<?php

declare(strict_types=1);

namespace TiMacDonald\Log;

use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert as PHPUnit;
use Psr\Log\LoggerInterface;
use Stringable;
use stdClass;

/**
 * @no-named-arguments
 */
class ChannelFake implements LoggerInterface
{
    use LogHelpers;

    private Dispatcher $dispatcher;

    /**
     * @var array<int, array{ level: mixed, message: string, context: array<string, mixed>, channel: string, times_channel_has_been_forgotten_at_time_of_writing_log: int }>
     */
    private array $logs = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $context = [];

    private int $timesForgotten = 0;

    private bool $isCurrentlyForgotten = false;

    /**
     * @var array{ '_': stdClass }
     */
    private array $sentinalContext;

    public function __construct(private string $name)
    {
        $this->sentinalContext = ['_' => new stdClass()];
    }

    public function assertLogged(string $level, ?callable $callback = null): ChannelFake
    {
        PHPUnit::assertTrue(
            $this->logged($level, $callback)->count() > 0,
            "An expected log with level [{$level}] was not logged in the [{$this->name}] channel."
        );

        return $this;
    }

    public function assertLoggedTimes(string $level, int $times, ?callable $callback = null): ChannelFake
    {
        PHPUnit::assertTrue(
            ($count = $this->logged($level, $callback)->count()) === $times,
            "A log with level [{$level}] was logged [{$count}] times instead of an expected [{$times}] times in the [{$this->name}] channel."
        );

        return $this;
    }

    public function assertNotLogged(string $level, ?callable $callback = null): ChannelFake
    {
        PHPUnit::assertTrue(
            ($count = $this->logged($level, $callback)->count()) === 0,
            "An unexpected log with level [{$level}] was logged [${count}] times in the [{$this->name}] channel."
        );

        return $this;
    }

    public function assertNothingLogged(): ChannelFake
    {
        PHPUnit::assertTrue(
            $this->logs()->isEmpty(),
            "Found [{$this->logs()->count()}] logs in the [{$this->name}] channel. Expected to find [0]."
        );

        return $this;
    }

    public function assertLoggedMessage(string $level, string $message): ChannelFake
    {
        return $this->assertLogged(
            $level,
            fn (string $loggedMessage): bool => $loggedMessage === $message
        );
    }

    public function assertForgotten(): ChannelFake
    {
        return $this->assertForgottenTimes(1);
    }

    public function assertForgottenTimes(int $times): ChannelFake
    {
        PHPUnit::assertSame(
            $times,
            $this->timesForgotten,
            "Expected the [{$this->name}] channel to be forgotten [{$times}] times. It was forgotten [{$this->timesForgotten}] times."
        );

        return $this;
    }

    public function assertNotForgotten(): ChannelFake
    {
        return $this->assertForgottenTimes(0);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function assertCurrentContext(array $context): ChannelFake
    {
        PHPUnit::assertSame(
            $context,
            $this->currentContext(),
            'Expected to find the context [' . json_encode($context, JSON_THROW_ON_ERROR) . '] in the [' . $this->name . '] channel. Found [' . json_encode((object) $this->currentContext()) . '] instead.'
        );

        return $this;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function assertHadContext(array $context): ChannelFake
    {
        PHPUnit::assertTrue(
            $this->allContextInstances()->containsStrict($context),
            'Expected to find the context [' . json_encode($context, JSON_THROW_ON_ERROR) . '] in the [' . $this->name . '] channel but did not.'
        );

        return $this;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function assertHadContextAtSetCall(array $context, int $time): ChannelFake
    {
        PHPUnit::assertGreaterThanOrEqual(
            $time,
            $this->allContextInstances()->count(),
            'Expected to find the context set at least [' . $time . '] times in the [' . $this->name . '] channel, but instead found it was set [' . $this->allContextInstances()->count() .'] times.'
        );

        PHPUnit::assertSame(
            $this->allContextInstances()->get($time - 1),
            $context,
            'Expected to find the context [' . json_encode($context, JSON_THROW_ON_ERROR) . '] at set call ['. $time .'] in the [' . $this->name . '] channel but did not.'
        );

        return $this;
    }

    public function assertContextSetTimes(int $times): ChannelFake
    {
        PHPUnit::assertSame(
            $this->allContextInstances()->count(),
            $times,
            'Expected to find the context set [' . $times . '] times in the [' . $this->name . '] channel, but instead found it set [' . $this->allContextInstances()->count() .'] times.'
        );

        return $this;
    }

    public function dump(?string $level = null): ChannelFake
    {
        $callback = $level === null
            ? fn (): bool => true
            : fn (array $log) => $log['level'] === $level;

        $this->logs()
            ->filter($callback)
            ->values()
            ->dump();

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @infection-ignore-all
     */
    public function dd(?string $level = null): never
    {
        $this->dump($level);

        exit(1);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function write(string $level, string $message, array $context = []): void
    {
        $this->log($level, $message, $context);
    }

    public function log($level, $message, array $context = []): void
    {
        $this->logs[] = [
            'level' => $level,
            'message' => (string) $message,
            'context' => array_merge($this->currentContext(), $context),
            'times_channel_has_been_forgotten_at_time_of_writing_log' => $this->timesForgotten,
            'channel' => $this->name,
        ];
    }

    public function getLogger(): ChannelFake
    {
        return $this;
    }

    public function listen(Closure $callback): void
    {
        //
    }

    public function getEventDispatcher(): Dispatcher
    {
        return $this->dispatcher;
    }

    public function setEventDispatcher(Dispatcher $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function withContext(array $context = []): ChannelFake
    {
        $this->context[] = array_merge($this->currentContext(), $context);

        return $this;
    }

    public function withoutContext(): ChannelFake
    {
        $this->context[] = [];

        return $this;
    }

    /**
     * @internal
     * @return Collection<int, array{ level: mixed, message: string, context: array<string, mixed>, channel: string, times_channel_has_been_forgotten_at_time_of_writing_log: int }>
     */
    public function logged(string $level, ?callable $callback = null): Collection
    {
        $callback = $callback ?? fn (): bool => true;

        return $this->logs()
            ->where('level', $level)
            ->filter(fn (array $log): bool => (bool) $callback(
                $log['message'],
                $log['context'],
                $log['times_channel_has_been_forgotten_at_time_of_writing_log']
            ))
            ->values();
    }

    /**
     * @internal
     * @return Collection<int, array{ level: mixed, message: string, context: array<string, mixed>, channel: string, times_channel_has_been_forgotten_at_time_of_writing_log: int }>
     */
    public function logs(): Collection
    {
        return Collection::make($this->logs);
    }

    /**
     * @internal
     */
    public function forget(): ChannelFake
    {
        $this->timesForgotten += 1;

        $this->isCurrentlyForgotten = true;

        return $this->clearContext();
    }

    public function initialize(): ChannelFake
    {
        $this->isCurrentlyForgotten = false;

        return $this;
    }

    /**
     * @internal
     */
    public function isCurrentlyForgotten(): bool
    {
        return $this->isCurrentlyForgotten;
    }

    /**
     * @return array<string, mixed>
     */
    private function currentContext(): array
    {
        /** @var array<string, mixed> */
        $context = Arr::last($this->context) ?? [];

        return $this->isNotSentinalContext($context)
            ? $context
            : [];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function allContextInstances(): Collection
    {
        return Collection::make($this->context)
            ->filter(fn (array $value): bool => $this->isNotSentinalContext($value))
            ->values();
    }

    /**
     * @param array<array-key, mixed> $context
     */
    private function isNotSentinalContext(array $context): bool
    {
        return $this->sentinalContext !== $context;
    }

    /**
     * @internal
     */
    public function clearContext(): ChannelFake
    {
        $this->context[] = $this->sentinalContext;

        return $this;
    }
}
