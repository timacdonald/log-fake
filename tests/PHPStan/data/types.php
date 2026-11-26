<?php

declare(strict_types=1);

namespace Tests\PHPStan\data;

use Illuminate\Support\Facades\Log;
use TiMacDonald\Log\LogEntry;

use function PHPStan\Testing\assertType;

function fakeOnlyMethods(): void
{
    // ChannelFake methods, forwarded by LogFake::__call()...
    assertType('TiMacDonald\Log\ChannelFake', Log::assertLogged(fn (): bool => true));
    assertType('TiMacDonald\Log\ChannelFake', Log::assertNothingLogged());
    assertType('Illuminate\Support\Collection<int, TiMacDonald\Log\LogEntry>', Log::logs());

    // LogFake methods (LogFake wins over ChannelFake)...
    assertType('TiMacDonald\Log\LogFake', Log::assertHasSharedContext([]));
    assertType('TiMacDonald\Log\LogFake', Log::assertChannelIsCurrentlyForgotten('single'));
    assertType('TiMacDonald\Log\LogFake', Log::dumpAll());
    assertType('Illuminate\Support\Collection<int, TiMacDonald\Log\LogEntry>', Log::allLogs());

    // Chaining...
    assertType('TiMacDonald\Log\ChannelFake', Log::assertLogged(fn (): bool => true)->assertNotLogged(fn (): bool => false));

    // Method names are case-insensitive...
    assertType('TiMacDonald\Log\ChannelFake', Log::ASSERTLOGGED(fn (): bool => true));

    // Closure parameter types come from the fake's PHPDoc...
    Log::assertLogged(function ($entry): bool {
        assertType('TiMacDonald\Log\LogEntry', $entry);

        return true;
    });
}

function realFacadeMethodsAreUnchanged(): void
{
    assertType('null', Log::info('x'));
    assertType('Psr\Log\LoggerInterface', Log::channel('x'));
    assertType('Psr\Log\LoggerInterface', Log::stack(['x']));
    assertType('Illuminate\Log\LogManager', Log::shareContext([]));
    assertType('array', Log::getChannels());

    // Implemented by the fakes, but forwarded to Illuminate\Log\Logger at runtime...
    assertType('Illuminate\Log\Logger', Log::withContext([]));
    assertType('Psr\Log\LoggerInterface', Log::getLogger());
}

function neverReturningMethods(): void
{
    if (random_int(0, 1) === 1) {
        assertType('never', Log::dd());
    }

    assertType('never', Log::ddAll(fn (LogEntry $entry): bool => true));
}
