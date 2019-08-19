<?php

namespace TiMacDonald\Log;

use Psr\Log\LoggerInterface;

class ChannelFake extends FakeLogger implements LoggerInterface
{
    /**
     * @var $log LogFake
     */
    protected $log;

    /**
     * @var $name string
     */
    protected $name;

    /**
     * ChannelFake constructor.
     *
     * @param $log
     * @param $name
     */
    public function __construct($log, $name)
    {
        $this->log = $log;
        $this->name = $name;
    }

    /**
     * @param $method
     * @param $arguments
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        $this->log->setCurrentChannel($this->name);

        $result = $this->log->{$method}(...$arguments);

        $this->log->setCurrentChannel(null);

        return $result;
    }

    /**
     * Log a message to the logs.
     *
     * @param string $level
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        $this->log->setCurrentChannel($this->name);

        $this->log->log($level, $message, $context);

        $this->log->setCurrentChannel(null);
    }
}
