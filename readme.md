<p align="center"><img src="/art/header.png" alt="Log Fake: a Laravel package by Tim MacDonald"></p>

# Log fake for Laravel

![CI](https://github.com/timacdonald/log-fake/workflows/CI/badge.svg) [![codecov](https://codecov.io/gh/timacdonald/log-fake/branch/master/graph/badge.svg)](https://codecov.io/gh/timacdonald/log-fake) [![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Ftimacdonald%2Flog-fake%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/timacdonald/log-fake/master) [![Total Downloads](https://poser.pugx.org/timacdonald/log-fake/downloads)](https://packagist.org/packages/timacdonald/log-fake)

A bunch of Laravel facades / services are able to be faked, such as the Dispatcher with `Bus::fake()`, to help with testing and assertions. This package gives you the ability to fake the logger in your app, and includes the ability to make assertions against channels, stacks, and a whole bunch more introduced in the logging overhaul from Laravel `5.6`.

## Version support

- **PHP**: 8.0, 8.1
- **Laravel**: 9.0
- **PHPUnit**: 9.0

You can find support for older versions in [previous releases](https://github.com/timacdonald/log-fake/releases).

## Installation

You can install using [composer](https://getcomposer.org/) from [Packagist](https://packagist.org/packages/timacdonald/log-fake).

```sh
composer require timacdonald/log-fake --dev
```

## Basic usage

```php
public function testItLogsWhenAUserAuthenticates()
{
    /*
     * Test setup.
     *
     * In the setup of your tests, you can call the following `bind` helper,
     * which will switch out the underlying log driver with the fake.
     */
    LogFake::bind();

    /*
     * Application implementation.
     *
     * In your application's implementation, you then utilise the logger, as you
     * normally would.
     */
    Log::info('User logged in.', ['user_id' => $user->id]);

    /*
     * Test assertions.
     *
     * Finally you can make assertions against the log channels, stacks, etc. to
     * ensure the expected logging occurred in your implementation.
     */
    Log::assertLogged(fn (LogEntry $log) =>
        $log->level === 'info'
        && $log->message === 'User logged in.' 
        && $log->context === ['user_id' => 5]
    );
}
```

## Channels

If you are logging to a specific channel (i.e. not the default channel) in your app, you need to also prefix your assertions in the same manner.

```php
public function testItLogsWhenAUserAuthenticates()
{
    // setup...
    LogFake::bind();

    // implementation...
    Log::channel('slack')->info('User logged in.', ['user_id' => $user->id]);

    // assertions...
    Log::channel('slack')->assertLogged(
        fn (LogEntry $log) => $log->message === 'User logged in.'
    );
}
```

## Stacks

If you are logging to a stack in your app, like with channels, you will need to prefix your assertions. Note that the order of the stack does not matter.

```php
public function testItLogsWhenAUserAuthenticates()
{
    // setup...
    LogFake::bind();

    // implementation...
    Log::stack(['stderr', 'single'])->info('User logged in.', ['user_id' => $user->id]);

    // assertions...
    Log::stack(['stderr', 'single'])->assertLogged(
        fn (LogEntry $log) => $log->message === 'User logged in.'
    );
}
```

That's it really. Now let's dig into the available assertions to improve your experience testing your applications logging.

## Available assertions

Remember that all assertions are relative to the channel or stack as shown above.

- [`assertLogged()`](#assertlogged)
- [`assertLoggedTimes()`](#assertloggedtimes)
- [`assertNotLogged()`](#assertnotlogged)
- [`assertNothingLogged()`](#assertnothinglogged)
- [`assertWasForgotten()`](#assertwasforgotten)
- [`assertWasForgottenTimes()`](#assertwasforgottentimes)
- [`assertWasNotForgotten()`](#assertwasnotforgotten)
- [`assertChannelIsCurrentlyForgotten()`](#assertchanneliscurrentlyforgotten)
- [`assertCurrentContext()`](#assertcurrentcontext)

### assertLogged()

Assert that a log was created.

#### Can be called on

- [x] Facade base (default channel)
- [x] Channels
- [x] Stacks

#### Example tests

```php
/*
 * implementation...
 */

Log::info('User logged in.');

/*
 * assertions...
 */

Log::assertLogged(
    fn (LogEntry $log) => $log->message === 'User logged in.'
); // ✅

Log::assertLogged(
    fn (LogEntry $log) => $log->level === 'critical'
); // ❌ as log had a level of `info`.
```

### assertLoggedTimes()

Assert that a log was created a specific number of times.

#### Can be called on

- [x] Facade base (default channel)
- [x] Channels
- [x] Stacks

#### Example tests

```php
/*
 * implementation...
 */

Log::info('Stripe request initiated.');

Log::info('Stripe request initiated.');

/*
 * assertions...
 */

Log::assertLoggedTimes(
    fn (LogEntry $log) => $log->message === 'Stripe request initiated.',
    2
); // ✅

Log::assertLoggedTimes(
    fn (LogEntry $log) => $log->message === 'Stripe request initiated.',
    99
); // ❌ as the log was created twice, not 99 times.
```

### assertNotLogged()

Assert that a log was never created.

#### Can be called on

- [x] Facade base (default channel)
- [x] Channels
- [x] Stacks

#### Example tests

```php
/*
 * implementation...
 */

Log::info('User logged in.');

/*
 * assertions...
 */

Log::assertNotLogged(
    fn (LogEntry $log) => $log->level === 'critical'
); // ✅

Log::assertNotLogged(
    fn (LogEntry $log) => $log->level === 'info'
); // ❌ as the level was `info`.
```

### assertNothingLogged()

Assert that no logs were created.

#### Can be called on

- [x] Facade base (default channel)
- [x] Channels
- [x] Stacks

#### Example tests

```php
/*
 * implementation...
 */

Log::channel('single')->info('User logged in.');

/*
 * assertions...
 */

Log::channel('stderr')->assertNothingLogged(); // ✅

Log::channel('single')->assertNothingLogged(); // ❌ as a log was created in the `single` channel.
```

### assertWasForgotten()

Assert that the channel was forgotten at least one time.

#### Can be called on

- [x] Facade base (default channel)
- [x] Channels
- [ ] Stacks

#### Example tests

```php
/*
 * implementation...
 */

Log::channel('single')->info('User logged in.');

Log::forgetChannel('single');

/*
 * assertions...
 */

Log::channel('single')->assertWasForgotten(); // ✅

Log::channel('stderr')->assertWasForgotten(); // ❌ as it was the `single` not the `stderr` channel that was not forgotten.
```

### assertWasForgottenTimes()

Assert that the channel was forgotten a specific number of times.

#### Can be called on

- [x] Facade base (default channel)
- [x] Channels
- [ ] Stacks

#### Example tests

```php
/*
 * implementation...
 */

Log::channel('single')->info('User logged in.');

Log::forgetChannel('single');

Log::channel('single')->info('User logged in.');

Log::forgetChannel('single');

/*
 * assertions...
 */

Log::channel('single')->assertWasForgottenTimes(2); // ✅

Log::channel('single')->assertWasForgottenTimes(99); // ❌ as the channel was forgotten twice, not 99 times.
```

### assertWasNotForgotten()

Assert that the channel was not forgotten.

#### Can be called on

- [x] Facade base (default channel)
- [x] Channels
- [ ] Stacks

#### Example tests

```php
/*
 * implementation...
 */

Log::channel('single')->info('User logged in.');

/*
 * assertions...
 */

Log::channel('single')->assertWasNotForgotten(); // ✅
```

### assertChannelIsCurrentlyForgotten()

Assert that a channel is _currently_ forgotten. This is distinct from [asserting that a channel _was_ forgotten](https://github.com/timacdonald/log-fake#assertwasforgotten).

#### Can be called on

- [x] Facade base ~(default channel)~
- [ ] Channels
- [ ] Stacks

#### Example tests

```php
/*
 * implementation...
 */

Log::channel('single')->info('xxxx');

Log::forgetChannel('single');

/*
 * assertions...
 */

Log::assertChannelIsCurrentlyForgotten('single'); // ✅

Log::assertChannelIsCurrentlyForgotten('stderr'); // ❌ as the `single` channel was forgotten, not the `stderr` channel.
```

### assertCurrentContext()

Assert that the channel currently has the specified context. It is possible to provide the expected context as an array or alternatively you can provide a truth-test closure to check the current context.

#### Can be called on

- [x] Facade base (default channel)
- [x] Channels
- [ ] Stacks

#### Example tests

```php
/*
 * implementation...
 */

Log::withContext([
    'app' => 'Acme CRM',
]);

Log::withContext([
    'env' => 'production',
]);

/*
 * assertions...
 */

Log::assertCurrentContext([
    'app' => 'Acme CRM',
    'env' => 'production',
]); // ✅

Log::assertCurrentContext(
    fn (array $context) => $context['app'] === 'Acme CRM')
); // ✅

Log::assertCurrentContext([
    'env' => 'production',
]); // ❌ missing the "app" key.

Log::assertCurrentContext(
    fn (array $context) => $context['env'] === 'develop')
); // ❌ the 'env' key is set to "production"
```

## Inspection

Sometimes when debugging tests it's useful to be able to take a peek at the messages that have been logged. There are a couple of helpers to assist with this.

### dump()

Dumps all the logs in the channel. You can also pass a truth-based closure to filter the logs that are dumped.

#### Can be called on

- [x] Facade base (default channel)
- [x] Channels
- [x] Stacks

```php
/*
 * implementation...
 */

Log::info('User logged in.');

Log::channel('slack')->alert('Stripe request initiated.');

/*
 * inspection...
 */

Log::dump();

// array:1 [
//   0 => array:4 [
//     "level" => "info"
//     "message" => "User logged in."
//     "context" => []
//     "channel" => "stack"
//   ]
// ]

Log::channel('slack')->dump();

// array:1 [
//   0 => array:4 [
//     "level" => "alert"
//     "message" => "Stripe request initiated."
//     "context" => []
//     "channel" => "slack"
//   ]
// ]
```

### dd()

The same as [`dump`](https://github.com/timacdonald/log-fake#dump), but also ends the execution.

### dumpAll()

Dumps the logs for all channels. Also accepts a truth-test closure to filter any logs.

#### Can be called on

- [x] Facade base ~(default channel)~
- [ ] Channels
- [ ] Stacks

#### Example usage

```php
/*
 * implementation...
 */

Log::info('User logged in.');

Log::channel('slack')->alert('Stripe request initiated.');

/*
 * inspection...
 */

Log::dumpAll();

// array:2 [
//   0 => array:4 [
//     "level" => "info"
//     "message" => "User logged in."
//     "context" => []
//     "times_channel_has_been_forgotten_at_time_of_writing_log" => 0
//     "channel" => "stack"
//   ]
//   1 => array:4 [
//     "level" => "alert"
//     "message" => "Stripe request initiated."
//     "context" => []
//     "times_channel_has_been_forgotten_at_time_of_writing_log" => 0
//     "channel" => "slack"
//   ]
// ]
```

### ddAll()

The same as [`dumpAll()`](https://github.com/timacdonald/log-fake#dumpall), but also ends the execution.

## Other APIs

### logs()

Get a collection of all log entries from a channel or stack.

#### Can be called on

- [x] Facade base (default channel)
- [x] Channels
- [x] Stacks

#### Example usage

```php
/*
 * implementation...
 */

Log::channel('slack')->info('User logged in.');

Log::channel('slack')->alert('Stripe request initiated.');

/*
 * example usage...
 */

$logs = Log::channel('slack')->logs();

assert($logs->count() === 2); ✅
```

### allLogs()

Similar to [`logs()`](https://github.com/timacdonald/log-fake#logs), except that it is called on the Facade base and returns a collection of logs from all the channels and stacks.

#### Can be called on

- [x] Facade base ~(default channel)~
- [ ] Channels
- [ ] Stacks

#### Example usage

```php
/*
 * implementation...
 */

Log::info('User logged in.');

Log::channel('slack')->alert('Stripe request initiated.');

/*
 * example usage...
 */

$logs = Log::allLogs();

assert($logs->count() === 2); ✅
```

## Credits

- [Tim MacDonald](https://github.com/timacdonald)
- [All Contributors](../../contributors)

And a special (vegi) thanks to [Caneco](https://twitter.com/caneco) for the logo ✨

## Thanksware

You are free to use this package, but I ask that you reach out to someone (not me) who has previously, or is currently, maintaining or contributing to an open source library you are using in your project and thank them for their work. Consider your entire tech stack: packages, frameworks, languages, databases, operating systems, frontend, backend, etc.
