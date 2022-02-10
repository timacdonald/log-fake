<?php

declare(strict_types=1);

namespace TiMacDonald\Log;

use function collect;
use function config;

use Illuminate\Support\Collection;
use function is_callable;
use PHPUnit\Framework\Assert as PHPUnit;
use Psr\Log\LoggerInterface;
use Symfony\Component\VarDumper\VarDumper;

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
     *
     * @return void
     */
    public function assertLogged($level, $callback = null)
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
     *
     * @return void
     */
    public function assertLoggedTimes($level, $times = 1, $callback = null)
    {
        PHPUnit::assertTrue(
            ($count = $this->logged($level, $callback)->count()) === $times,
            "The expected log with level [{$level}] was logged {$count} times instead of {$times} times in {$this->currentChannel()}."
        );
    }

    /**
     * @param mixed $level
     * @param callable|null $callback
     *
     * @return void
     */
    public function assertNotLogged($level, $callback = null)
    {
        PHPUnit::assertTrue(
            $this->logged($level, $callback)->count() === 0,
            "The unexpected log with level [{$level}] was logged in {$this->currentChannel()}."
        );
    }

    /**
     * @return void
     */
    public function assertNothingLogged()
    {
        PHPUnit::assertTrue($this->logsInCurrentChannel()->isEmpty(), "Logs were created in {$this->currentChannel()}.");
    }

    /**
     * @param mixed $level
     * @param string $message
     *
     * @return void
     */
    public function assertLoggedMessage($level, $message)
    {
        $this->assertLogged($level, static function (string $loggedMessage) use ($message): bool {
            return $loggedMessage === $message;
        });
    }

    /**
     * @param mixed $level
     * @param callable|null $callback
     *
     * @return \Illuminate\Support\Collection
     */
    public function logged($level, $callback = null)
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
     *
     * @return bool
     */
    public function hasLogged($level)
    {
        return $this->logsOfLevel($level)->isNotEmpty();
    }

    /**
     * @param mixed $level
     *
     * @return bool
     */
    public function hasNotLogged($level)
    {
        return ! $this->hasLogged($level);
    }

    /**
     * @param mixed $level
     *
     * @return \Illuminate\Support\Collection
     */
    protected function logsOfLevel($level)
    {
        return $this->logsInCurrentChannel()->filter(static function (array $log) use ($level): bool {
            return $log['level'] === $level;
        });
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    protected function logsInCurrentChannel()
    {
        return Collection::make($this->logs)->filter(function (array $log): bool {
            return $this->currentChannelIs($log['channel']);
        });
    }

    /**
     * @param mixed $level
     * @param string $message
     *
     * @return void
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
     *
     * @return void
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
    public function channel($channel = null)
    {
        return $this->driver($channel);
    }

    /**
     * @param mixed $driver
     *
     * @return \TiMacDonald\Log\ChannelFake
     */
    public function driver($driver = null)
    {
        return new ChannelFake($this, $driver);
    }

    /**
     * @param mixed $channel
     *
     * @return \TiMacDonald\Log\ChannelFake
     */
    public function stack(array $channels, $channel = null)
    {
        return $this->driver('Stack:'.$this->createStackChannelName($channels, $channel));
    }

    /**
     * @param array $channels
     * @param mixed $channel
     *
     * @return string
     */
    protected function createStackChannelName($channels, $channel)
    {
        return collect($channels)->sort()->prepend($channel ?? 'default_testing_stack_channel')->implode('.');
    }

    /**
     * @param mixed $name
     *
     * @return void
     */
    public function setCurrentChannel($name)
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
     *
     * @return bool
     */
    protected function currentChannelIs($channel)
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
     *
     * @return void
     */
    public function setDefaultDriver($name)
    {
        config()->set('logging.default', $name);
    }

    /**
     * @return self
     */
    public function getLogger()
    {
        return $this;
    }

    /**
     * Dump all logs in the current channel and end the script
     *
     * @param mixed $level optional level to filter to
     *
     * @return void
     */
    public function dd($level = null)
    {
        $this->dump($level);

        exit(1);
    }

    /**
     * Dump all logs in the current channel
     *
     * @param mixed $level optional level to filter to
     *
     * @return self
     */
    public function dump($level = null)
    {
        $logs = $this->currentChannel ? $this->logsInCurrentChannel()
            : collect($this->logs);

        if ($level) {
            $logs = $logs->filter(function (array $log) use ($level): bool {
                return $log['level'] === $level;
            });
        }

        VarDumper::dump($logs->all());

        return $this;
    }

    /**
     * @return void
     */
    public function listen()
    {
        //
    }

    /**
     * @return void
     */
    public function extend()
    {
        //
    }

    /**
     * @return void
     */
    public function getEventDispatcher()
    {
        //
    }

    /**
     * @return void
     */
    public function setEventDispatcher()
    {
        //
    }
}
