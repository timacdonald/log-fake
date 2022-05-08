<?php

declare(strict_types=1);

namespace TiMacDonald\Log;

use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Log\Logger;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert as PHPUnit;
use Psr\Log\LoggerInterface;

/**
 * @no-named-arguments
 */
class ChannelFake implements LoggerInterface
{
    use LogHelpers;

    private Dispatcher $dispatcher;

    /**
     * @var array<int, LogEntry>
     */
    private array $logs = [];

    /**
     * @var array<int, array<array-key, mixed>>
     */
    private array $context = [];

    private int $timesForgotten = 0;

    private bool $isCurrentlyForgotten = false;

    public function __construct(private string $name)
    {
        //
    }

    /**
     * @link https://github.com/timacdonald/log-fake#dump Documentation
     * @param (Closure(LogEntry): bool) $callback
     */
    public function dump(?Closure $callback = null): ChannelFake
    {
        dump(($this->logs()
            ->filter($callback ?? fn () => true)
            ->values()
            ->toArray()));

        return $this;
    }

    /**
     * @link https://github.com/timacdonald/log-fake#dd Documentation
     * @codeCoverageIgnore
     * @infection-ignore-all
     * @param (Closure(LogEntry): bool) $callback
     */
    public function dd(?Closure $callback = null): never
    {
        $this->dump($callback);

        exit(1);
    }

    /**
     * @link https://github.com/timacdonald/log-fake#assertlogged Documentation
     * @param (Closure(LogEntry): bool) $callback
     */
    public function assertLogged(Closure $callback, ?string $message = null): ChannelFake
    {
        PHPUnit::assertTrue(
            $this->logged($callback)->count() > 0,
            $message ?? "Expected log was not created in the [{$this->name}] channel."
        );

        return $this;
    }

    /**
     * @link https://github.com/timacdonald/log-fake#assertloggedtimes Documentation
     * @param (Closure(LogEntry): bool) $callback
     */
    public function assertLoggedTimes(Closure $callback, int $times, ?string $message = null): ChannelFake
    {
        PHPUnit::assertTrue(
            ($count = $this->logged($callback)->count()) === $times,
            $message ?? "Expected log was not created [{$times}] times in the [{$this->name}] channel. Instead was created [{$count}] times."
        );

        return $this;
    }

    /**
     * @link https://github.com/timacdonald/log-fake#assertnotlogged Documentation
     * @param (Closure(LogEntry): bool) $callback
     */
    public function assertNotLogged(Closure $callback, ?string $message = null): ChannelFake
    {
        return $this->assertLoggedTimes($callback, 0, $message);
    }

    /**
     * @link https://github.com/timacdonald/log-fake#assertnothinglogged Documentation
     */
    public function assertNothingLogged(?string $message = null): ChannelFake
    {
        PHPUnit::assertTrue(
            $this->logs()->isEmpty(),
            $message ?? "Expected [0] logs to be created in the [{$this->name}] channel. Found [{$this->logs()->count()}] instead."
        );

        return $this;
    }

    /**
     * @link https://github.com/timacdonald/log-fake#assertwasforgotten Documentation
     */
    public function assertWasForgotten(?string $message = null): ChannelFake
    {
        PHPUnit::assertTrue(
            $this->timesForgotten > 0,
            $message ?? "Expected the [{$this->name}] channel to be forgotten at least once. It was forgotten [0] times."
        );

        return $this;
    }

    /**
     * @link https://github.com/timacdonald/log-fake#assertwasforgottentimes Documentation
     */
    public function assertWasForgottenTimes(int $times, ?string $message = null): ChannelFake
    {
        PHPUnit::assertSame(
            $times,
            $this->timesForgotten,
            $message ?? "Expected the [{$this->name}] channel to be forgotten [{$times}] times. It was forgotten [{$this->timesForgotten}] times."
        );

        return $this;
    }

    /**
     * @link https://github.com/timacdonald/log-fake#assertwasnotforgotten Documentation
     */
    public function assertWasNotForgotten(?string $message = null): ChannelFake
    {
        return $this->assertWasForgottenTimes(0, $message);
    }

    /**
     * @link https://github.com/timacdonald/log-fake#assertcurrentcontext Documentation
     * @param (Closure(array<array-key, mixed>): bool)|array<array-key, mixed> $context
     */
    public function assertCurrentContext(Closure|array $context, ?string $message = null): ChannelFake
    {
        if ($context instanceof Closure) {
            PHPUnit::assertTrue(
                (bool) $context($this->currentContext()), /** @phpstan-ignore-line */
                $message ?? 'Unexpected context found in the [' . $this->name . '] channel. Found [' . json_encode((object) $this->currentContext(), JSON_THROW_ON_ERROR) . '].'
            );
        } else {
            PHPUnit::assertSame(
                $context,
                $this->currentContext(),
                'Expected to find the context [' . json_encode($context, JSON_THROW_ON_ERROR) . '] in the [' . $this->name . '] channel. Found [' . json_encode((object) $this->currentContext(), JSON_THROW_ON_ERROR) . '] instead.'
            );
        }

        return $this;
    }

    /**
     * @see Logger::log()
     * @see LoggerInterface::log()
     */
    public function log($level, $message, array $context = []): void
    {
        $this->logs[] = new LogEntry(
            $level,
            $message,
            array_merge($this->currentContext(), $context),
            $this->timesForgotten,
            $this->name
        );
    }

    /**
     * @see Logger::write()
     * @param \Illuminate\Contracts\Support\Arrayable<array-key, mixed>|\Illuminate\Contracts\Support\Jsonable|\Illuminate\Support\Stringable|array<array-key, mixed>|string $message
     * @param array<array-key, mixed> $context
     */
    public function write(string $level, $message, array $context = []): void
    {
        $this->log($level, $message, $context); /** @phpstan-ignore-line */
    }

    /**
     * @see Logger::withContext()
     * @param array<array-key, mixed> $context
     */
    public function withContext(array $context = []): ChannelFake
    {
        $this->context[] = array_merge($this->currentContext(), $context);

        return $this;
    }

    /**
     * @see Logger::withoutContext()
     */
    public function withoutContext(): ChannelFake
    {
        $this->context[] = [];

        return $this;
    }

    /**
     * @see Logger::listen()
     */
    public function listen(Closure $callback): void
    {
        //
    }

    /**
     * @see Logger::getLogger()
     */
    public function getLogger(): ChannelFake
    {
        return $this;
    }

    /**
     * @see Logger::getEventDispatcher()
     */
    public function getEventDispatcher(): Dispatcher
    {
        return $this->dispatcher;
    }

    /**
     * @see Logger::setEventDispatcher()
     */
    public function setEventDispatcher(Dispatcher $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param (Closure(LogEntry): bool) $callback
     * @return Collection<int, LogEntry>
     */
    private function logged(Closure $callback): Collection
    {
        return $this->logs()
            ->filter(fn (LogEntry $log): bool => (bool) $callback($log)) /** @phpstan-ignore-line */
            ->values();
    }

    /**
     * @return Collection<int, LogEntry>
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

    /**
     * @internal
     */
    public function remember(): ChannelFake
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
     * @internal
     */
    public function clearContext(): ChannelFake
    {
        $this->context[] = [];

        return $this;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function currentContext(): array
    {
        /** @var array<array-key, mixed> */
        return Arr::last($this->context) ?? [];
    }
}
