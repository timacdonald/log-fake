<?php

declare(strict_types=1);

namespace Tests\PHPStan\data;

use Illuminate\Support\Facades\Log;
use TiMacDonald\Log\LogEntry;

/**
 * Every fake-only method, called through the facade with valid arguments.
 * LogFacadeFakeMethodsTest ensures each one is present in this file.
 */
function allMethods(): void
{
    $callback = fn (LogEntry $entry): bool => $entry->level === 'info';

    // ChannelFake...
    Log::dump();
    Log::dump($callback);
    Log::assertLogged($callback);
    Log::assertLogged($callback, 'message');
    Log::assertLoggedTimes($callback, 2);
    Log::assertLoggedTimes($callback, 2, 'message');
    Log::assertNotLogged($callback);
    Log::assertNotLogged($callback, 'message');
    Log::assertNothingLogged();
    Log::assertNothingLogged('message');
    Log::assertWasForgotten();
    Log::assertWasForgotten('message');
    Log::assertWasForgottenTimes(2);
    Log::assertWasForgottenTimes(2, 'message');
    Log::assertWasNotForgotten();
    Log::assertWasNotForgotten('message');
    Log::assertCurrentContext(['foo' => 'bar']);
    Log::assertCurrentContext(fn (array $context): bool => $context === [], 'message');
    Log::logs();
    Log::forget();
    Log::remember();
    Log::isCurrentlyForgotten();
    Log::clearContext();

    // LogFake...
    Log::dumpAll();
    Log::dumpAll($callback);
    Log::assertChannelIsCurrentlyForgotten('single');
    Log::assertChannelIsCurrentlyForgotten('single', 'message');
    Log::assertHasSharedContext(['foo' => 'bar']);
    Log::assertHasSharedContext(fn (array $context): bool => $context === [], 'message');
    Log::allLogs();

    // Never return, so they go last...
    if (random_int(0, 1) === 1) {
        Log::dd($callback);
    }
    Log::ddAll($callback);
}
