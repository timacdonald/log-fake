<?php

declare(strict_types=1);

use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;
use Psr\Log\LoggerInterface;

function config(): Repository
{
    $config = Container::getInstance()->make('config');

    \assert($config instanceof Repository);

    return $config;
}

function app(): LoggerInterface
{
    $logger = Container::getInstance()->make('log');

    \assert($logger instanceof LoggerInterface);

    return $logger;
}
