<?php

declare(strict_types=1);

namespace TiMacDonald\Log;

use Closure;
use Illuminate\Log\LogManager;
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

    /**
     * @api
     * @link https://github.com/timacdonald/log-fake#basic-usage Documentation
     */
    public static function bind(): LogFake
    {
        $instance = new LogFake();

        Log::swap($instance);

        return $instance;
    }

    /**
     * @api
     * @param (CLosure(LogEntry): bool) $callback
     */
    public function dumpAll(?Closure $callback = null): LogFake
    {
        dump($this->allLogs()
            ->filter($callback ?? fn () => true)
            ->values()
            ->toArray());

        return $this;
    }

    /**
     * @api
     * @codeCoverageIgnore
     * @infection-ignore-all
     * @param (Closure(LogEntry): bool) $callback
     */
    public function ddAll(?Closure $callback = null): never
    {
        $this->dumpAll($callback);

        exit(1);
    }

    /**
     * @api
     * @link https://github.com/timacdonald/log-fake#assertchanneliscurrentlyforgotten Documentation
     */
    public function assertChannelIsCurrentlyForgotten(string $name, ?string $message = null): LogFake
    {
        PHPUnit::assertTrue(
            ($this->channels[$name] ?? null)?->isCurrentlyForgotten(),
            $message ?? "Expected to find the [{$name}] channel to be forgotten. It was not."
        );

        return $this;
    }

    /**
     * @api
     * @see LogManager::build()
     * @param array<string, mixed> $config
     */
    public function build(array $config): ChannelFake
    {
        return $this->driver(
            'ondemand::'.json_encode($config)
        )->clearContext();
    }

    /**
     * @api
     * @see LogManager::stack()
     * @param array<int, string> $channels
     */
    public function stack(array $channels, ?string $channel = null): ChannelFake
    {
        $name = $this->parseStackDriver($channels, $channel);

        $this->stacks[$name] ??= new StackFake($name);

        return $this->stacks[$name]->clearContext();
    }


    /**
     * @api
     * @see LogManager::channel()
     */
    public function channel(?string $channel = null): ChannelFake
    {
        return $this->driver($channel);
    }

    /**
     * @api
     * @see LogManager::driver()
     */
    public function driver(?string $driver = null): ChannelFake
    {
        $name = $this->parseChannelDriver($driver);

        $channel = $this->channels[$name] ??= new ChannelFake($name);

        return $channel->remember();
    }

    /**
     * @api
     * @see LogManager::getDefaultDriver()
     */
    public function getDefaultDriver(): ?string
    {
        /** @var ?string */
        return config()->get('logging.default');
    }

    /**
     * @api
     * @see LogManager::setDefaultDriver()
     */
    public function setDefaultDriver(string $name): void
    {
        config()->set('logging.default', $name);
    }

    /**
     * @api
     * @see LogManager::extend()
     */
    public function extend(string $driver, Closure $callback): LogFake
    {
        return $this;
    }

    /**
     * @api
     * @see LogManager::forgetChannel()
     */
    public function forgetChannel(?string $driver = null): LogFake
    {
        $this->channel($this->parseChannelDriver($driver))->forget();

        return $this;
    }

    /**
     * @api
     * @see LogManager::getChannels()
     * @return array<string, ChannelFake>
     */
    public function getChannels(): array
    {
        // TODO this could just return non-forgotten channels now.
        return $this->channels;
    }

    /**
     * @api
     * @see LogManager::log()
     * @param mixed $level
     */
    public function log($level, $message, array $context = []): void
    {
        $this->driver()->log($level, $message, $context);
    }

    /**
     * @api
     * @see LogManager::__call()
     * @param array<string, mixed> $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->driver()->{$method}(...$parameters); /** @phpstan-ignore-line */
    }

    /**
     * @param array<int, string> $channels
     */
    private function parseStackDriver(array $channels, ?string $channel): string
    {
        return 'stack::' . ($channel ?? 'unnamed') . ':' . Collection::make($channels)->sort()->implode(',');
    }

    private function parseChannelDriver(?string $driver): string
    {
        return $driver ?? $this->getDefaultDriver() ?? 'null';
    }

    /**
     * @return Collection<int, LogEntry>
     */
    private function allLogs(): Collection
    {
        /** @var Collection<int, LogEntry> */
        return $this->allChannelsAndStacks()->flatMap(fn (ChannelFake $channel): Collection => $channel->logs());
    }

    /**
     * @return Collection<string, ChannelFake>
     */
    private function allChannelsAndStacks(): Collection
    {
        return Collection::make($this->channels)->merge($this->stacks);
    }
}
