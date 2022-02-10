<?php

declare(strict_types=1);

namespace TiMacDonald\Log;

trait LogHelpers
{
    /**
     * Log an emergency message to the logs.
     *
     * @param string $message
     *
     * @return void
     */
    public function emergency($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * Log an alert message to the logs.
     *
     * @param string $message
     *
     * @return void
     */
    public function alert($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * Log a critical message to the logs.
     *
     * @param string $message
     *
     * @return void
     */
    public function critical($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * Log an error message to the logs.
     *
     * @param string $message
     *
     * @return void
     */
    public function error($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * Log a warning message to the logs.
     *
     * @param string $message
     *
     * @return void
     */
    public function warning($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * Log a notice to the logs.
     *
     * @param string $message
     *
     * @return void
     */
    public function notice($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * Log an informational message to the logs.
     *
     * @param string $message
     *
     * @return void
     */
    public function info($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * Log a debug message to the logs.
     *
     * @param string $message
     *
     * @return void
     */
    public function debug($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }
}
