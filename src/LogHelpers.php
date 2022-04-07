<?php

declare(strict_types=1);

namespace TiMacDonald\Log;

use Illuminate\Log\Logger;
use Illuminate\Log\LogManager;

/**
 * @internal
 * @no-named-arguments
 */
trait LogHelpers
{
    /**
     * @api
     * @see LogManager::emergency()
     * @see Logger::emergency()
     */
    public function emergency($message, $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    /**
     * @api
     * @see LogManager::alert()
     * @see Logger::alert()
     */
    public function alert($message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    /**
     * @api
     * @see LogManager::critical()
     * @see Logger::critical()
     */
    public function critical($message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    /**
     * @api
     * @see LogManager::error()
     * @see Logger::error()
     */
    public function error($message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * @api
     * @see LogManager::warning()
     * @see Logger::warning()
     */
    public function warning($message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * @api
     * @see LogManager::notice()
     * @see Logger::notice()
     */
    public function notice($message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    /**
     * @api
     * @see LogManager::info()
     * @see Logger::info()
     */
    public function info($message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * @api
     * @see LogManager::debug()
     * @see Logger::debug()
     */
    public function debug($message, array $context = []): void
    {
        // TODO: specify
        $this->log('debug', $message, $context);
    }

    /**
     * @api
     * @see LogManager::log()
     * @see Logger::log()
     */
    abstract public function log($level, $message, array $context = []): void;
}
