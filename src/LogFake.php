<?php

declare(strict_types=1);

namespace TiMacDonald\Log;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Stringable;

/**
 * @mixin ChannelFake
 */
class LogFake implements LoggerInterface
{
    use LogHelpers;

    /**
     * @var array<string, ChannelFake>
     */
    private array $channels = [];

    /**
     * @var array<string, ChannelFake>
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

    /**
     * @param mixed $level
     */
    public function log($level, string|Stringable $message, array $context = []): void
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

        $stack = new ChannelFake($name);

        $stack = $this->stacks[$name] ??= new ChannelFake($name);

        return $stack->withoutContext();
    }

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

    /**

    public function driver(?string $driver = null): ChannelFake
    {
        $name = $this->parseChannelDriver($driver);

        return $this->channels[$name] ??= new ChannelFake($name);
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
    public function getChannels()
    {
        // TODO: document that this will return all channels, even forgotten ones
        // or alternatively introduce another function to do that.
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
     * @return Collection<int, array{level: mixed, message: string, context: array<string, mixed>, channel: string, times_channel_has_been_forgotten_at_time_of_writing_log: int}>
     */
    private function allLogs(): Collection
    {
        return $this->channelsAndStacks()->flatMap(fn (ChannelFake $channel): Collection => $channel->logs());
    }

    private function channelsAndStacks(): Collection
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
