<?php

declare(strict_types=1);

namespace TiMacDonald\Log;

use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert as PHPUnit;
use Psr\Log\LoggerInterface;
use Stringable;

class ChannelFake implements LoggerInterface
{
    use LogHelpers;

    private Dispatcher $dispatcher;

    /**
     * @var array<int, array{level: mixed, message: string, context: array<string, mixed>, channel: string, times_channel_has_been_forgotten_at_time_of_writing_log: int}>
     */
    private array $logs = [];

    /**
     * @var array<string, mixed>
     */
    private array $context = [];

    private int $timesForgotten = 0;

    public function __construct(private string $name)
    {
        //
    }

    public function assertLogged(string $level, ?callable $callback = null): void
    {
        PHPUnit::assertTrue(
            $this->logged($level, $callback)->count() > 0,
            "An expected log with level [{$level}] was not logged in the [{$this->name}] channel."
        );
    }

    public function assertLoggedTimes(string $level, int $times, ?callable $callback = null): void
    {
        PHPUnit::assertTrue(
            ($count = $this->logged($level, $callback)->count()) === $times,
            "A log with level [{$level}] was logged [{$count}] times instead of an expected [{$times}] times in the [{$this->name}] channel."
        );
    }

    public function assertNotLogged(string $level, ?callable $callback = null): void
    {
        PHPUnit::assertTrue(
            ($count = $this->logged($level, $callback)->count()) === 0,
            "An unexpected log with level [{$level}] was logged [${count}] times in the [{$this->name}] channel."
        );
    }

    public function assertNothingLogged(): void
    {
        PHPUnit::assertTrue(
            $this->logs()->isEmpty(),
            "Found [{$this->logs()->count()}] logs in the [{$this->name}] channel. Expected to find [0]."
        );
    }

    public function assertLoggedMessage(string $level, string $message): void
    {
        $this->assertLogged($level, fn (string $loggedMessage): bool => $loggedMessage === $message);
    }

    public function assertForgotten(): void
    {
        $this->assertForgottenTimes(1);
    }

    public function assertForgottenTimes(int $times): void
    {
        PHPUnit::assertSame(
            $times,
            $this->timesForgotten,
            "Expected the [{$this->name}] channel to be forgotten [{$times}] times. It was forgotten [{$this->timesForgotten}] times."
        );
    }

    public function assertNotForgotten(): void
    {
        $this->assertForgottenTimes(0);
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
        // TODO: add symfony var dumper as a recommended composer dependecy like
        // laravel does.
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

    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->logs[] = [
            'level' => $level,
            'message' => (string) $message,
            'context' => array_merge($this->context, $context),
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
        // TODO: ensure this is scoped to a channel.
        $this->context = array_merge($this->context, $context);

        return $this;
    }

    public function withoutContext(): ChannelFake
    {
        $this->context = [];

        return $this;
    }

    /**
     * @internal
     * @return Collection<int, array{level: mixed, message: string, context: array<string, mixed>, channel: string, times_channel_has_been_forgotten_at_time_of_writing_log: int}>
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
     * @return Collection<int, array{level: mixed, message: string, context: array<string, mixed>, channel: string, times_channel_has_been_forgotten_at_time_of_writing_log: int}>
     */
    public function logs(): Collection
    {
        return Collection::make($this->logs);
    }

    /**
     * @internal
     */
    public function forget(): void
    {
        // TODO: should this forgotten logic be extracted out to the parent?
        // TODO should this clear the context?
        $this->timesForgotten += 1;
    }
}
