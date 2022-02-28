<?php

declare(strict_types=1);

namespace TiMacDonald\Log;

use Stringable;
use Closure;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @mixin \TiMacDonald\Log\LogFake
 */
class ChannelFake implements LoggerInterface
{
    use LogHelpers;

    protected LogFake $log;

    protected ?string $name;

    public function __construct(LogFake $log, ?string $name)
    {
        $this->log = $log;

        $this->name = $name;
    }

    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->proxy(function () use ($level, $message, $context): void {
            $this->log->log($level, $message, $context);
        });
    }

    public function __call(string $method, array $arguments): mixed
    {
        return $this->proxy(function () use ($method, $arguments): mixed {
            return $this->log->{$method}(...$arguments);
        });
    }

    private function proxy(Closure $closure): mixed
    {
        $this->log->setCurrentChannel($this->name);

        $result = $closure();

        $this->log->setCurrentChannel(null);

        return $result;
    }

    public function dumpAll(string $level = null): never
    {
        throw new RuntimeException('LogFake::dumpAll() should not be called from a channel.');
    }

    public function ddAll(string $level = null): never
    {
        throw new RuntimeException('LogFake::ddAll() should not be called from a channel.');
    }
}
