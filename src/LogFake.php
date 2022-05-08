<?php

declare(strict_types=1);

namespace TiMacDonald\Log;

use Closure;
use Illuminate\Log\LogManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Assert as PHPUnit;
use Psr\Log\LoggerInterface;
use Symfony\Component\VarDumper\VarDumper;

/**
 * @no-named-arguments
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
     * @var array<string, StackFake>
     */
    private array $stacks = [];

    /**
     * @link https://github.com/timacdonald/log-fake#basic-usage Documentation
     */
    public static function bind(): LogFake
    {
        $instance = new LogFake();

        Log::swap($instance);

        return $instance;
    }

    /**
     * @link https://github.com/timacdonald/log-fake#dumpall Documentation
     * @param (CLosure(LogEntry): bool) $callback
     */
    public function dumpAll(?Closure $callback = null): LogFake
    {
        VarDumper::dump($this->allLogs()
            ->filter($callback ?? fn () => true)
            ->values()
            ->toArray());

        return $this;
    }

    /**
     * @link https://github.com/timacdonald/log-fake#ddall Documentation
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
     * @see LogManager::build()
     * @param array<string, mixed> $config
     */
    public function build(array $config): ChannelFake
    {
        return $this->driver(
            'ondemand::'.json_encode((object) $config, JSON_THROW_ON_ERROR)
        )->clearContext();
    }

    /**
     * @see LogManager::stack()
     * @param array<int, string> $channels
     */
    public function stack(array $channels, ?string $channel = null): ChannelFake
    {
        $name = self::parseStackDriver($channels, $channel);

        $this->stacks[$name] ??= new StackFake($name);

        return $this->stacks[$name]->clearContext();
    }


    /**
     * @see LogManager::channel()
     */
    public function channel(?string $channel = null): ChannelFake
    {
        return $this->driver($channel);
    }

    /**
     * @see LogManager::driver()
     */
    public function driver(?string $driver = null): ChannelFake
    {
        $name = $this->parseChannelDriver($driver);

        $channel = $this->channels[$name] ??= new ChannelFake($name);

        return $channel->remember();
    }

    /**
     * @see LogManager::getDefaultDriver()
     */
    public function getDefaultDriver(): ?string
    {
        /** @var ?string */
        return Config::get('logging.default');
    }

    /**
     * @see LogManager::setDefaultDriver()
     */
    public function setDefaultDriver(string $name): void
    {
        Config::set('logging.default', $name);
    }

    /**
     * @see LogManager::extend()
     */
    public function extend(string $driver, Closure $callback): LogFake
    {
        return $this;
    }

    /**
     * @see LogManager::forgetChannel()
     */
    public function forgetChannel(?string $driver = null): LogFake
    {
        $this->channel($this->parseChannelDriver($driver))->forget();

        return $this;
    }

    /**
     * @see LogManager::getChannels()
     * @return array<string, ChannelFake>
     */
    public function getChannels(): array
    {
        return $this->channels;
    }

    /**
     * @see LogManager::log()
     * @see LoggerInterface::log()
     * @param mixed $level
     */
    public function log($level, $message, array $context = []): void
    {
        $this->driver()->log($level, $message, $context);
    }

    /**
     * @see LogManager::__call()
     * @param array<string, mixed> $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->driver()->{$method}(...$parameters); /** @phpstan-ignore-line */
    }

    /**
     * @return Collection<int, LogEntry>
     */
    public function allLogs(): Collection
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

    private function parseChannelDriver(?string $driver): string
    {
        return $driver ?? $this->getDefaultDriver() ?? 'null';
    }

    /**
     * @param array<int, string> $channels
     */
    private static function parseStackDriver(array $channels, ?string $channel): string
    {
        return 'stack::' . ($channel ?? 'unnamed') . ':' . Collection::make($channels)->sort()->implode(',');
    }
}
