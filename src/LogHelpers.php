<?php

namespace TiMacDonald\Log;

use Illuminate\Log\Logger;
use Illuminate\Log\LogManager;

trait LogHelpers
{
    /**
     * @see LogManager::emergency()
     * @see Logger::emergency()
     *
     * @param  string|\Stringable  $message
     */
    public function emergency($message, $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    /**
     * @see LogManager::alert()
     * @see Logger::alert()
     *
     * @param  string|\Stringable  $message
     */
    public function alert($message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    /**
     * @see LogManager::critical()
     * @see Logger::critical()
     *
     * @param  string|\Stringable  $message
     */
    public function critical($message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    /**
     * @see LogManager::error()
     * @see Logger::error()
     *
     * @param  string|\Stringable  $message
     */
    public function error($message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * @see LogManager::warning()
     * @see Logger::warning()
     *
     * @param  string|\Stringable  $message
     */
    public function warning($message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * @see LogManager::notice()
     * @see Logger::notice()
     *
     * @param  string|\Stringable  $message
     */
    public function notice($message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    /**
     * @see LogManager::info()
     * @see Logger::info()
     *
     * @param  string|\Stringable  $message
     */
    public function info($message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * @see LogManager::debug()
     * @see Logger::debug()
     *
     * @param  string|\Stringable  $message
     */
    public function debug($message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * @see LogManager::log()
     * @see Logger::log()
     *
     * @param  string|\Stringable  $message
     */
    abstract public function log($level, $message, array $context = []): void;
}
