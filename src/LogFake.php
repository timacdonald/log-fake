<?php

declare(strict_types=1);

namespace TiMacDonald\Log;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Stringable;

use function assert;
use function config;
use function is_string;

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
     */
    public function ddAll(?string $level = null): never
    {
        $this->dumpAll($level);

        exit(1);
    }

    public function channel(?string $channel = null): ChannelFake
    {
        return $this->driver($channel);
    }

    /**
     * @param array<string> $channels
     */
    public function stack(array $channels, ?string $channel = null): ChannelFake
    {
        return $this->driver('Stack:'.$this->createStackChannelName($channels, $channel));
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
        return $this->channels[$this->parseDriver($driver)] ??= new ChannelFake($this->parseDriver($driver));
    }

    public function getDefaultDriver(): ?string
    {
        $driver = config()->get('logging.default');

        assert(is_string($driver) || $driver === null);

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
        return $this->channels;
    }

    public function forgetChannel(?string $driver = null): LogFake
    {
        $this->channel($this->parseDriver($driver))->forget();

        return $this;
    }

    /**
     * @param array<string> $channels
     */
    private function createStackChannelName(array $channels, ?string $channel): string
    {
        return Collection::make($channels)
            ->sort()
            ->prepend($channel ?? 'default_testing_stack_channel')
            ->implode('.');
    }

    private function parseDriver(?string $driver): string
    {
        return $driver ?? $this->getDefaultDriver() ?? 'null';
    }

    private function channels(): Collection
    {
        return Collection::make($this->channels);
    }

    public function allLogs(): Collection
    {
        return $this->channels()->flatMap(fn (ChannelFake $channel): Collection => $channel->logs());
    }

    /**
     * @param mixed $level
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->driver()->log($level, $message, $context);
    }

    /**
     * @param array<mixed> $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->driver()->$method(...$parameters);
    }
}
