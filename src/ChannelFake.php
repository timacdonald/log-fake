<?php

namespace TiMacDonald\Log;

use Psr\Log\LoggerInterface;

class ChannelFake implements LoggerInterface
{
    use LogHelpers;

    /**
     * The logger to proxy calls to.
     *
     * @var \TiMacDonald\Log\LogFake
     */
    protected $log;

    /**
     * The name of the current channel.
     *
     * @var string
     */
    protected $name;

    /**
     * Create a new instance.
     *
     * @param \TiMacDonald\Log\LogFake $log
     * @param string $name
     * @return void
     */
    public function __construct($log, $name)
    {
        $this->log = $log;

        $this->name = $name;
    }

    /**
     * Proxy a 'log' call to the logger.
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        $this->proxy(function () use ($level, $message, $context) {
            $this->log->log($level, $message, $context);
        });
    }

    /**
     * Handle dynamic calls to the instance.
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return $this->proxy(function () use ($method, $arguments) {
            return $this->log->{$method}(...$arguments);
        });
    }

    /**
     * Proxy calls to the logger.
     *
     * @param \Closure $closure
     * @return mixed
     */
    private function proxy($closure)
    {
        $this->log->setCurrentChannel($this->name);

        $result = $closure();

        $this->log->setCurrentChannel(null);

        return $result;
    }
}
