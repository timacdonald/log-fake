<?php

declare(strict_types=1);

namespace TiMacDonald\Log;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Assert as PHPUnit;
use Psr\Log\LoggerInterface;

/**
 * @mixin ChannelFake
 * @no-named-arguments
 */
class LogFake implements LoggerInterface
{
    use LogHelpers;

    /**
     * @var array<string, ChannelFake>
     */
    private array $channels = [];

    /**
     * @var array<string, StackFake>
     */
    private array $stacks = [];

    public static function bind(): LogFake
    {
        $instance = new LogFake();

        Log::swap($instance);

        return $instance;
    }

    public function dumpAll(?string $level = null): LogFake
    {
        $callback = $level === null
            ? fn (): bool => true
            : fn (array $log): bool => $log['level'] === $level;

        $this->allLogs()
            ->filter($callback)
            ->values()
            ->dump();

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @infection-ignore-all
     */
    public function ddAll(?string $level = null): never
    {
        $this->dumpAll($level);

        exit(1);
    }

    public function assertChannelIsCurrentlyForgotten(string $name): LogFake
    {
        $channel = $this->channels[$name] ?? null;

        PHPUnit::assertNotNull(
            $channel,
            "Unable to assert that the [{$name}] channel has been forgotten. The channel was never built."
        );

        PHPUnit::assertTrue(
            $channel->isCurrentlyForgotten(),
            "Expected to find the [{$name}] channel to be forgotten. It was not."
        );

        return $this;
    }

    /**
     * @param mixed $level
     */
    public function log($level, $message, array $context = []): void
    {
        $this->driver()->log($level, $message, $context);
    }

    public function channel(?string $channel = null): ChannelFake
    {
        return $this->driver($channel);
    }

    /**
     * @param array<int, string> $channels
     */
    public function stack(array $channels, ?string $channel = null): ChannelFake
    {
        $name = $this->parseStackDriver($channels, $channel);

        $this->stacks[$name] ??= new StackFake($name);

        return $this->stacks[$name]->clearContext();
    }

    /**
     * @param array<int, string> $channels
     */
    private function parseStackDriver(array $channels, ?string $channel): string
    {
        return 'stack::' . ($channel ?? 'unnamed') . ':' . Collection::make($channels)->sort()->implode(',');
    }

    /**
     * @param array<string, mixed> $config
     */
    public function build(array $config): ChannelFake
    {
        return $this->driver('ondemand');
    }

    public function driver(?string $driver = null): ChannelFake
    {
        $name = $this->parseChannelDriver($driver);

        $channel = $this->channels[$name] ??= new ChannelFake($name);

        return $channel->initialize();
    }

    public function getDefaultDriver(): ?string
    {
        /** @var ?string */
        $driver = config()->get('logging.default');

        return $driver;
    }

    public function setDefaultDriver(string $name): void
    {
        config()->set('logging.default', $name);
    }

    public function extend(string $driver, Closure $callback): void
    {
        //
    }

    /**
     * @return array<string, ChannelFake>
     */
    public function getChannels(): array
    {
        return $this->channels;
    }

    public function forgetChannel(?string $driver = null): LogFake
    {
        $this->channel($this->parseChannelDriver($driver))->forget();

        return $this;
    }

    private function parseChannelDriver(?string $driver): string
    {
        return $driver ?? $this->getDefaultDriver() ?? 'null';
    }

    /**
     * @return Collection<int, array{ level: mixed, message: string, context: array<string, mixed>, channel: string, times_channel_has_been_forgotten_at_time_of_writing_log: int }>
     */
    private function allLogs(): Collection
    {
        /** @var Collection<int, array{ level: mixed, message: string, context: array<string, mixed>, channel: string, times_channel_has_been_forgotten_at_time_of_writing_log: int }> */
        return $this->allChannelsAndStacks()->flatMap(fn (ChannelFake $channel): Collection => $channel->logs());
    }

    /**
     * @return Collection<string, ChannelFake>
     */
    private function allChannelsAndStacks(): Collection
    {
        return Collection::make($this->channels)->merge($this->stacks);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->driver()->{$method}(...$parameters);
    }
}
