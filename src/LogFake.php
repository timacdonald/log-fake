<?php

declare(strict_types=1);

namespace TiMacDonald\Log;

use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Assert as PHPUnit;
use Stringable;
use Psr\Log\LoggerInterface;
use Symfony\Component\VarDumper\VarDumper;
use function collect;
use function config;
use function is_callable;

class LogFake implements LoggerInterface
{
    use LogHelpers;

    /**
     * @var array<array{level: mixed, message: string|Stringable, context: array<string, mixed>, channel: string}>
     */
    protected array $logs = [];

    protected ?string $currentChannel = null;

    /**
     * @var array<string, mixed>
     */
    protected array $context = [];

    /**
     * @var array<string>
     */
    protected array $channels = [];

    protected Dispatcher $dispatcher;

    public static function bind(): LogFake
    {
        $instance = new LogFake();

        Log::swap($instance);

        return $instance;
    }

    public function assertLogged(string $level, callable|int|null $callback = null): void
    {
        if ($callback === null || is_callable($callback)) {
            PHPUnit::assertTrue(
                $this->logged($level, $callback)->count() > 0,
                "The expected log with level [{$level}] was not logged in {$this->currentChannel()}."
            );

            return;
        }

        $this->assertLoggedTimes($level, $callback);
    }

    public function assertLoggedTimes(string $level, int $times = 1, ?callable $callback = null): void
    {
        PHPUnit::assertTrue(
            ($count = $this->logged($level, $callback)->count()) === $times,
            "The expected log with level [{$level}] was logged {$count} times instead of {$times} times in {$this->currentChannel()}."
        );
    }

    public function assertNotLogged(string $level, ?callable $callback = null): void
    {
        PHPUnit::assertTrue(
            $this->logged($level, $callback)->count() === 0,
            "The unexpected log with level [{$level}] was logged in {$this->currentChannel()}."
        );
    }

    public function assertNothingLogged(): void
    {
        PHPUnit::assertTrue($this->logsInCurrentChannel()->isEmpty(), "Logs were created in {$this->currentChannel()}.");
    }

    public function assertLoggedMessage(string $level, string $message): void
    {
        $this->assertLogged($level, static function (string $loggedMessage) use ($message): bool {
            return $loggedMessage === $message;
        });
    }

    public function dump(?string $level = null): LogFake
    {
        if ($level === null) {
            VarDumper::dump($this->logsInCurrentChannel()->all());
        } else {
            VarDumper::dump($this->logsOfLevel($level)->all());
        }

        return $this;
    }

    public function dumpAll(?string $level = null): LogFake
    {
        if ($level === null) {
            VarDumper::dump($this->logs);
        } else {
            Collection::make($this->logs)
                ->filter(static function (array $log) use ($level): bool {
                    return $log['level'] === $level;
                })
                ->values()
                ->pipe(function (Collection $logs): void {
                    VarDumper::dump($logs->all());
                });
        }

        return $this;
    }

    public function dd(?string $level = null): never
    {
        $this->dump($level);

        exit(1);
    }

    public function ddAll(?string $level = null): never
    {
        $this->dumpAll($level);

        exit(1);
    }

    public function logged(string $level, ?callable $callback = null): Collection
    {
        if ($callback === null) {
            return $this->logsOfLevel($level)->filter(static function (): bool {
                return true;
            })->values();
        }

        return $this->logsOfLevel($level)->filter(static function (array $log) use ($callback): bool {
            return (bool) $callback($log['message'], $log['context']);
        })->values();
    }

    public function hasLogged(string $level): bool
    {
        return $this->logsOfLevel($level)->isNotEmpty();
    }

    public function hasNotLogged(string $level): bool
    {
        return ! $this->hasLogged($level);
    }

    protected function logsOfLevel(string $level): Collection
    {
        return $this->logsInCurrentChannel()->filter(static function (array $log) use ($level): bool {
            return $log['level'] === $level;
        })->values();
    }

    protected function logsInCurrentChannel(): Collection
    {
        return Collection::make($this->logs)->filter(function (array $log): bool {
            return $this->currentChannelIs($log['channel']);
        })->values();
    }

    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->logs[] = [
            'level' => $level,
            'message' => $message,
            'context' => array_merge($this->context, $context),
            'channel' => $this->currentChannel(),
        ];
    }

    public function channel(?string $channel = null): ChannelFake
    {
        return $this->driver($channel);
    }

    public function driver(?string $driver = null): ChannelFake
    {
        if (! in_array($driver, $this->channels, true)) {
            $this->channels[] = $driver ?? $this->getDefaultDriver();
        }

        return new ChannelFake($this, $driver);
    }

    public function stack(array $channels, ?string $channel = null): ChannelFake
    {
        return $this->driver('Stack:'.$this->createStackChannelName($channels, $channel));
    }

    protected function createStackChannelName(array $channels, ?string $channel): string
    {
        return Collection::make($channels)->sort()->prepend($channel ?? 'default_testing_stack_channel')->implode('.');
    }

    /**
     * @internal
     */
    public function setCurrentChannel(?string $name): void
    {
        $this->currentChannel = $name;
    }

    protected function currentChannel(): string
    {
        return $this->currentChannel ?? $this->getDefaultDriver();
    }

    protected function currentChannelIs(string $channel): bool
    {
        return $this->currentChannel() === $channel;
    }

    public function getDefaultDriver(): string
    {
        $default = config()->get('logging.default') ?? '';

        assert(is_string($default));

        return $default;
    }

    public function setDefaultDriver(string $name): void
    {
        config()->set('logging.default', $name);
    }

    public function getLogger(): LogFake
    {
        return $this;
    }

    public function listen(Closure $callback): void
    {
        //
    }

    public function extend(string $driver, Closure $callback): void
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
    public function withContext(array $context = []): LogFake
    {
        $this->context = array_merge($this->context, $context);

        return $this;
    }

    public function withoutContext(): LogFake
    {
        $this->context = [];

        return $this;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function build(array $config): ChannelFake
    {
        return $this->driver('ondemand');
    }

    /**
     * @return array<string, ChannelFake>
     */
    public function getChannels()
    {
        return Collection::make($this->channels)
            ->mapWithKeys(function (string $channel): array {
                return [$channel => $this->driver($channel)];
            })
            ->all();
    }

    public function forgetChannel(?string $driver = null): LogFake
    {
        unset($this->channels[$driver ?? $this->getDefaultDriver()]);

        return $this;
    }

    public function allLogs(): array
    {
        return $this->logs;
    }
}
