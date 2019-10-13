# Log fake for Laravel

[![Latest Stable Version](https://poser.pugx.org/timacdonald/log-fake/v/stable)](https://packagist.org/packages/timacdonald/log-fake) [![Total Downloads](https://poser.pugx.org/timacdonald/log-fake/downloads)](https://packagist.org/packages/timacdonald/log-fake) [![License](https://poser.pugx.org/timacdonald/log-fake/license)](https://packagist.org/packages/timacdonald/log-fake)

A bunch of Laravel facades / services are able to be faked, such as the Dispatcher with `Bus::fake()`, to help with testing and assertions. This package gives you the ability to fake the logger in your app, and includes the ability to make assertions against channels and stacks introduced in logging overhaul in Laravel `5.6`.

## Installation

You can install using [composer](https://getcomposer.org/) from [Packagist](https://packagist.org/packages/timacdonald/log-fake)

```
$ composer require timacdonald/log-fake --dev
```

For Laravel versions `>=5.6 && <6.0` install `1.3.0` of the this package...

```
$ composer require timacdonald/log-fake:1.3.0 --dev
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

### assertLoggedMessage($level, $message)

```php
Log::assertLoggedMessage('info', 'User registered');

Log::channel('slack')->assertLoggedMessage('alert', 'It is 5pm, go home');

Log::stack(['bugsnag', 'sentry'])->assertLoggedMessage('critical', 'Perform evasive maneuvers');
```

### assertLoggedTimes($level, $times = 1, $callback = null)

```php
Log::assertLoggedTimes('info', 5);

Log::channel('slack')->assertLoggedTimes('alert', 5);

Log::stack(['bugsnag', 'sentry'])->assertLoggedTimes('critical', 5);

// with a callback

Log::assertLogged('info', 5, function ($message, $context) {
    return str_contains($message, 'Donuts');
});

Log::channel('slack')->assertLogged('alert', 5, function ($message, $context) {
    return str_contains($message, '5pm');
});

Log::stack(['bugsnag', 'sentry'])->assertLogged('critical', 5, function ($message, $context) {
    return str_contains($message, 'evasive maneuvers');
});
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
Log::assertNothingLogged();

Log::channel('slack')->assertNothingLogged();

Log::stack(['bugsnag', 'sentry'])->assertNothingLogged();
```

## Thanksware

You are free to use this package, but I ask that you reach out to someone (not me) who has previously, or is currently, maintaining or contributing to an open source library you are using in your project and thank them for their work. Consider your entire tech stack: packages, frameworks, languages, databases, operating systems, frontend, backend, etc.
