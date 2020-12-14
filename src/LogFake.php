<?php

declare(strict_types=1);

namespace TiMacDonald\Log;

use function collect;
use function config;
use Illuminate\Support\Collection;
use function is_callable;
use PHPUnit\Framework\Assert as PHPUnit;
use Psr\Log\LoggerInterface;

class LogFake implements LoggerInterface
{
    use LogHelpers;

    /**
     * @var array
     */
    protected $logs = [];

    /**
     * @var mixed
     */
    protected $currentChannel;

    /**
     * @param mixed $level
     * @param callable|int|null $callback
     */
    public function assertLogged($level, $callback = null): void
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

    /**
     * @param mixed $level
     * @param int $times
     * @param callable|null $callback
     */
    public function assertLoggedTimes($level, $times = 1, $callback = null): void
    {
        PHPUnit::assertTrue(
            ($count = $this->logged($level, $callback)->count()) === $times,
            "The expected log with level [{$level}] was logged {$count} times instead of {$times} times in {$this->currentChannel()}."
        );
    }

    /**
     * @param mixed $level
     * @param callable|null $callback
     */
    public function assertNotLogged($level, $callback = null): void
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

    /**
     * @param mixed $level
     * @param string $message
     */
    public function assertLoggedMessage($level, $message): void
    {
        $this->assertLogged($level, static function (string $loggedMessage) use ($message): bool {
            return $loggedMessage === $message;
        });
    }

    /**
     * @param mixed $level
     * @param callable|null $callback
     */
    public function logged($level, $callback = null): Collection
    {
        if ($callback === null) {
            return $this->logsOfLevel($level)->filter(static function (): bool {
                return true;
            });
        }

        return $this->logsOfLevel($level)->filter(static function (array $log) use ($callback): bool {
            return (bool) $callback($log['message'], $log['context']);
        });
    }

    /**
     * @param mixed $level
     */
    public function hasLogged($level): bool
    {
        return $this->logsOfLevel($level)->isNotEmpty();
    }

    /**
     * @param mixed $level
     */
    public function hasNotLogged($level): bool
    {
        return ! $this->hasLogged($level);
    }

    /**
     * @param mixed $level
     */
    protected function logsOfLevel($level): Collection
    {
        return $this->logsInCurrentChannel()->filter(static function (array $log) use ($level): bool {
            return $log['level'] === $level;
        });
    }

    protected function logsInCurrentChannel(): Collection
    {
        return Collection::make($this->logs)->filter(function (array $log): bool {
            return $this->currentChannelIs($log['channel']);
        });
    }

    /**
     * @param mixed $level
     * @param string $message
     */
    public function log($level, $message, array $context = []): void
    {
        $this->logs[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'channel' => $this->currentChannel(),
        ];
    }

    /**
     * @param mixed $level
     * @param string $message
     */
    public function write($level, $message, array $context = []): void
    {
        $this->log($level, $message, $context);
    }

    /**
     * @param mixed $channel
     *
     * @return \TiMacDonald\Log\ChannelFake
     */
    public function channel($channel = null): ChannelFake
    {
        return $this->driver($channel);
    }

    /**
     * @param mixed $driver
     *
     * @return \TiMacDonald\Log\ChannelFake
     */
    public function driver($driver = null): ChannelFake
    {
        return new ChannelFake($this, $driver);
    }

    /**
     * @param mixed $channel
     *
     * @return \TiMacDonald\Log\ChannelFake
     */
    public function stack(array $channels, $channel = null): ChannelFake
    {
        return $this->driver('Stack:'.$this->createStackChannelName($channels, $channel));
    }

    /**
     * @param array $channels
     * @param mixed $channel
     */
    protected function createStackChannelName($channels, $channel): string
    {
        return collect($channels)->sort()->prepend($channel ?? 'default_testing_stack_channel')->implode('.');
    }

    /**
     * @param mixed $name
     */
    public function setCurrentChannel($name): void
    {
        $this->currentChannel = $name;
    }

    /**
     * @return mixed
     */
    public function currentChannel()
    {
        return $this->currentChannel ?? $this->getDefaultDriver();
    }

    /**
     * @param mixed $channel
     */
    protected function currentChannelIs($channel): bool
    {
        return $this->currentChannel() === $channel;
    }

    /**
     * @return mixed
     */
    public function getDefaultDriver()
    {
        return config()->get('logging.default');
    }

    /**
     * @param string $name
     */
    public function setDefaultDriver($name): void
    {
        config()->set('logging.default', $name);
    }

    public function getLogger(): self
    {
        return $this;
    }

    public function listen(): void
    {
        //
    }

    public function extend(): void
    {
        //
    }

    public function getEventDispatcher(): void
    {
        //
    }

    public function setEventDispatcher(): void
    {
        //
    }
}
