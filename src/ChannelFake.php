<?php

declare(strict_types=1);

namespace TiMacDonald\Log;

use Closure;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @mixin \TiMacDonald\Log\LogFake
 */
class ChannelFake implements LoggerInterface
{
    use LogHelpers;

    /**
     * @var \TiMacDonald\Log\LogFake
     */
    protected $log;

    /**
     * @var mixed
     */
    protected $name;

    /**
     * @param \TiMacDonald\Log\LogFake $log
     * @param mixed $name
     */
    public function __construct($log, $name)
    {
        $this->log = $log;

        $this->name = $name;
    }

    /**
     * @param mixed $level
     * @param string $message
     *
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        $this->proxy(function () use ($level, $message, $context): void {
            $this->log->log($level, $message, $context);
        });
    }

    /**
     * @param mixed $level optional level to filter to
     *
     * @return never
     */
    public function dumpAll($level = null)
    {
        throw new RuntimeException('LogFake::dumpAll() should not be called from a channel.');
    }

    /**
     * @param mixed $level optional level to filter to
     *
     * @return never
     */
    public function ddAll($level = null)
    {
        throw new RuntimeException('LogFake::ddAll() should not be called from a channel.');
    }

    /**
     * @param string $method
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return $this->proxy(
            /**
             * @return mixed
             */
            function () use ($method, $arguments) {
                return $this->log->{$method}(...$arguments);
            }
        );
    }

    /**
     * @param Closure $closure
     *
     * @return mixed
     */
    private function proxy($closure)
    {
        $this->log->setCurrentChannel($this->name);

        /** @var mixed */
        $result = $closure();

        $this->log->setCurrentChannel(null);

        return $result;
    }
}
