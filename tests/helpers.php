<?php

use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;

function config(): Repository
{
    $config = Container::getInstance()->make('config');

    assert($config instanceof Repository);

    return $config;
}
