# Changelog

## v2.0.0

## Version support

- Drop PHP `^7.1` support.
- Drop `illuminate/config` version `~5.6.0 || ~5.7.0 || ~5.8.0 || ^6.0 || ^7.0 || ^8.0` support.
- Add `illuminate/config` version `^9.0` support.
- Drop `illuminate/container` version `~5.6.0 || ~5.7.0 || ~5.8.0 || ^6.0 || ^7.0 || ^8.0` support.
- Add `illuminate/container` version `^9.0` support.
- Drop `illuminate/support` version `~5.6.0 || ~5.7.0 || ~5.8.0 || ^6.0 || ^7.0 || ^8.0` support.
- Add `illuminate/support` version `^9.0` support.
- Drop `phpunit/phpunit` version `^7.0 || ^8.0` support.
- Add `psr/log` version `^3.0` support.

## Feature

- Added a changelog
- Drop support for named arguments.
- Return `$this` from assertion functions to allow for assertion chaining.
- Add `LogFake::bind()` helper to bind the fake to the container.
- When no channel name is specified and no configuration default is found, the channel name now defaults to `"null"` to match the framework.

```diff
- Log::swap(new LogFake());
+ LogFake::bind();
```

- Add `LogFake::dumpAll()` to dump all logs from every channel and stack to help with debugging (thanks @brentkelly).

```php
Log::dumpAll();

// dumps all channel / stack logs to console...
```
- Add `LogFake::ddAll()` to dump all logs from every channel and stack and then die to help with debugging (thanks @brentkelly).

```php
Log::ddAll();

// dumps all channel / stack logs to console and stops execution...
```

- Add `LogFake::assertChannelIsCurrentlyForgotten($channel)` to assert against the _current_ forgotten state of a channel.

```php
Log::channel('slack')->info('xxxx');

Log::forgetChannel('slack');

Log::assertChannelIsCurrentlyForgotten('slack'); // ✅
```

- Add `LogFake::build()` to support to "ondemand" feature of the Log Manager. This channel will always be named `"ondemand"`.

```php
Log::build($config)->info('xxxx');

Log::channel('ondemand')->assertLogged('xxxx'); // ✅

```

- Add `ChannelFake::assertWasForgotten()` to assert that a channel was forgotten at least once.

```php
Log::channel('slack')->info('xxxx');
Log::forgetChannel('slack');

Log::channel('slack')->info('xxxx');

Log::channel('slack')->assertWasForgotten(); // ✅
```

- Add `ChannelFake::assertWasForgottenTimes($times)` to assert that a channel was forgotten the specified number of times.

```php
Log::channel('slack')->info('xxxx');
Log::forgetChannel('slack');

Log::channel('slack')->info('xxxx');
Log::forgetChannel('slack');

Log::channel('slack')->assertWasForgottenTimes(2); // ✅
```

- Add `ChannelFake::assertWasNotForgotten()` to assert that a channel was never forgotten.

```php
Log::channel('slack')->info('xxxx');

Log::channel('slack')->assertWasNotForgotten(); // ✅
```

- Add `ChannelFake::assertCurrentContext($context)` to assert that the context is currently present in the channel. A Stack cannot utilise this function as a stack's context is reset each time it is resolved from the LogManager.

```php
Log::channel('slack')->withContext(['foo' => 'bar']);

Log::channel('slack')->assertCurrentContext(['foo' => 'bar']);
```

- Add `ChannelFake::assertHadContext($context)` to assert that the context was as some point present in the channel.

```php
Log::channel('slack')->withContext(['foo' => 'bar']);
Log::channel('slack')->withoutContext();

Log::channel('slack')->assertHadContext(['foo' => 'bar']); // ✅
```

- Add `ChannelFake::assertHadContextAtSetCall($context, $call)` to assert that the specified context was set at the call to `with{out}Context()` count.

```php
Log::channel('slack')->withContext(['foo' => 'bar']);
Log::channel('slack')->withoutContext([]);
Log::channel('slack')->withContext(['bar' => 'baz']);

Log::channel('slack')->assertHadContextAtSetCall(['foo' => 'bar'], 1); // ✅
Log::channel('slack')->assertHadContextAtSetCall([], 2); // ✅
Log::channel('slack')->assertHadContextAtSetCall(['bar' => 'baz'], 3); // ✅
```

- Add `ChannelFake::assertContextSetTimes($times)` to assert that context has been set the specified number of times.

```php
Log::channel('slack')->withContext(['foo' => 'bar']);
Log::channel('slack')->withoutContext([]);

Log::assertContextSetTimes(2); // ✅
```

- Add `ChannelFake::dump(?$level)` to dump the logs from the channel to help with debugging (thanks @brentkelly).

```php
Log::channel('slack')->dump();

// dumps logs to console...
```

- Add `ChannelFake::dd(?$level)` to dump all logs from the channel and then die to help with debugging (thanks @brentkelly).

```php
Log::channel('slack')->dump();

// dumps logs to console and stops execution...
```

- Document that failing expectation exception messages are no longer considered breaking changes.
- **BREAKING**: Update failing expectation exception messages.
- **BREAKING**: The logger is no longer responsible for housing everything. i.e. the channels no longer proxy things back to the logger and instead they now house their own logs. _This should only be breaking if you are extending any of the classes provided by this package._
- **BREAKING**: Make protected methods / properties private. _This should only be breaking if you are extending any of the classes provided by this package._
- **BREAKING**: Add type declarations where possible. _This should only be breaking if you are extending any of the classes provided by this package or if you are passing in wrong value types_.
- **BREAKING**: Callable parameters have been replaced by Closure based parameters.

```diff
- Log::assertLogged('info', [$this, 'someCallableMethod']);
+ Log::assertLogged('info', fn ($message, $context) => $this->someCallableMethod($message, $context));
```

- **BREAKING**: `LogFake::assertLogged()` no longer accepts an integer as the second parameter. Utilise the `LogFake::assertLoggedTimes()` function instead.

```diff
- Log::assertLogged('info', 5);
+ Log::assertLoggedTimes('info', 5);
```

- **BREAKING**: `LogFake::assertLoggedTimes()` no longer has a default value of `1` for the `$times` parameter.

```diff
- LogFake::assertLoggedTimes('info');
+ LogFake::assertLoggedTimes('info', 1);
```

- **BREAKING**: The `ChannelFake::logged()` is now `@internal` and is not meant for public consumption.
- **BREAKING**: The `ChannelFake::hasLogged()` function has been removed.
- **BREAKING**: The `ChannelFake::hasNotLogged()` function has been removed.
- **BREAKING**: Several helper functions have been made private, `@internal`, or removed completely. _This should only be breaking if you are extending any of the classes provided by this package._
- **BREAKING**: Stacks can no longer be resolved via the `LogFake::channel($name)` function and should be resolved via the `LogFake::stack($channels, $channel)` function instead. _This should only be a breaking change if you are referencing a stack name directly._

```diff
Log::stack(['slack', 'stderr'], 'my-stack')->info('xxxx');


g Log::channel('Stack:my-stack,slack,stderr')->assert(/* ... */);
+ Log::stack(['slack', 'stderr'], 'my-stack')->assert(/* ... */);
```

## Chore

- Run CI linting against PHP `8.1`.
- Drop CI testing against PHP `^7.0`.
- Drop CI testing against Laravel `~5.*`, `^6.0`, `^7.0`, and `^8.0`.
- Drop CI testing against PHPUnit `^7.0` and `^8.0`.
- Drop Psalm type checking CI step in favour of PHPStan.
- Drop composer normalize CI step.
- Add CI testing against Laravel `^9.0`.
- Add CI testing against PHP `8.1`.
- Migrate PHP-CS-Fixer config..
- Add PHP-CS-Fixer rules.
- Move dev dependencies to a dedicated `composer.json` via `bamarni/composer-bin-plugin`.

