<?php

declare(strict_types=1);

namespace TiMacDonald\Log;

use Stringable;

trait LogHelpers
{
    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function error(string|Stringable $message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function info(string|Stringable $message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function write(string $level, string $message, array $context = []): void
    {
        $this->log($level, $message, $context);
    }

    /**
     * @param mixed $level
     */
    abstract public function log($level, string|Stringable $message, array $context = []): void;
}
