<?php

declare(strict_types=1);

namespace TiMacDonald\Log;

use Illuminate\Log\Logger;
use Illuminate\Log\LogManager;

/**
 * @no-named-arguments
 */
trait LogHelpers
{
    /**
     * @see LogManager::emergency()
     * @see Logger::emergency()
     */
    public function emergency($message, $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    /**
     * @see LogManager::alert()
     * @see Logger::alert()
     */
    public function alert($message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    /**
     * @see LogManager::critical()
     * @see Logger::critical()
     */
    public function critical($message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    /**
     * @see LogManager::error()
     * @see Logger::error()
     */
    public function error($message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * @see LogManager::warning()
     * @see Logger::warning()
     */
    public function warning($message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * @see LogManager::notice()
     * @see Logger::notice()
     */
    public function notice($message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    /**
     * @see LogManager::info()
     * @see Logger::info()
     */
    public function info($message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * @see LogManager::debug()
     * @see Logger::debug()
     */
    public function debug($message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * @see LogManager::log()
     * @see Logger::log()
     */
    abstract public function log($level, $message, array $context = []): void;
}
