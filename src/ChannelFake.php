<?php

namespace TiMacDonald\Log;

class ChannelFake
{
    protected $log;

    protected $name;

    public function __construct($log, $name)
    {
        $this->log = $log;

        $this->name = $name;
    }

    public function __call($method, $arguments)
    {
        $this->log->setCurrentChannel($this->name);

        $result = $this->log->{$method}(...$arguments);

        $this->log->setCurrentChannel(null);

        return $result;
    }
}
