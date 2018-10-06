# Log fake for Laravel

[![Latest Stable Version](https://poser.pugx.org/timacdonald/log-fake/v/stable)](https://packagist.org/packages/timacdonald/log-fake) [![Total Downloads](https://poser.pugx.org/timacdonald/log-fake/downloads)](https://packagist.org/packages/timacdonald/log-fake) [![License](https://poser.pugx.org/timacdonald/log-fake/license)](https://packagist.org/packages/timacdonald/log-fake)

A bunch of Laravel facades / services are able to be faked, such as the Dispatcher with `Bus::fake()`, to help with testing and assertions. This package gives you the ability to fake the logger in your app, and includes the ability to make assertions against channels and stacks introduced in logging overhaul in Laravel `5.6`.

## Installation

You can install using [composer](https://getcomposer.org/) from [Packagist](https://packagist.org/packages/timacdonald/log-fake)

```
$ composer require timacdonald/log-fake
```

## Basic usage

```php
use TiMacDonald\Log\LogFake;
use Illuminate\Support\Facades\Log;

//...

Log::swap(new LogFake);

Log::info('Donuts have arrived');

Log::assertLogged('info', function ($message, $context) {
    return str_contains($message, 'Donuts');
});
```

## Channels

If you are logging to a specific channel in your app, such as Slack with `Log::channel('slack')->critical('It is 5pm, go home')`, you need to also prefix your assertions in the same manner.

```php
use TiMacDonald\Log\LogFake;
use Illuminate\Support\Facades\Log;

//...

Log::swap(new LogFake);

Log::channel('slack')->alert('It is 5pm, go home');

Log::channel('slack')->assertLogged('alert'); // ✅ passes

// without the channel prefix...

Log::assertLogged('alert');  // ❌ fails
```

## Stacks

If you are logging to a stack in your app, like with channels, you will need to prefix your assertions. Note that the order of the stack does not matter.

```php
use TiMacDonald\Log\LogFake;
use Illuminate\Support\Facades\Log;

//...

Log::swap(new LogFake);

Log::stack(['bugsnag', 'sentry'])->critical('Perform evasive maneuvers');


Log::stack(['bugsnag', 'sentry'])->assertLogged('critical');  // ✅ passes

// without the stack prefix...

Log::assertLogged('critical'); // ❌ fails
```

## Available assertions

All assertions are relative to the channel or stack as shown in the previous examples.

### assertLogged($level, $callback = null)

```php
Log::assertLogged('info');

Log::channel('slack')->assertLogged('alert');

Log::stack(['bugsnag', 'sentry'])->assertLogged('critical');

// with a callback

Log::assertLogged('info', function ($message, $context) {
    return str_contains($message, 'Donuts');
});

Log::channel('slack')->assertLogged('alert', function ($message, $context) {
    return str_contains($message, '5pm');
});

Log::stack(['bugsnag', 'sentry'])->assertLogged('critical', function ($message, $context) {
    return str_contains($message, 'evasive maneuvers');
});
```

### assertLoggedTimes($level, $times = 1)

```php
Log::assertLoggedTimes('info', 5);

Log::channel('slack')->assertLoggedTimes('alert', 5);

Log::stack(['bugsnag', 'sentry'])->assertLoggedTimes('critical', 5);
```

### assertNotLogged($level, $callback = null)

```php
Log::assertNotLogged('info');

Log::channel('slack')->assertNotLogged('alert');

Log::stack(['bugsnag', 'sentry'])->assertNotLogged('critical');

// with a callback

Log::assertNotLogged('info', function ($message, $context) {
    return str_contains($message, 'Donuts');
});

Log::channel('slack')->assertNotLogged('alert' , function ($message, $context) {
    return str_contains($message, '5pm');
});

Log::stack(['bugsnag', 'sentry'])->assertNotLogged('critical', function ($message, $context) {
    return str_contains($message, 'evasive maneuvers');
});
```

### assertNothingLogged()

```php
Log::assertNothingLogged('info');

Log::channel('slack')->assertNothingLogged('alert');

Log::stack(['bugsnag', 'sentry'])->assertNothingLogged('critical');
```