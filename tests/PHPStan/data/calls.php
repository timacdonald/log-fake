<?php

declare(strict_types=1);

namespace Tests\PHPStan\data;

use Illuminate\Support\Facades\Log;
use TiMacDonald\Log\LogEntry;

function calls(): void
{
    Log::assertLogged(fn (LogEntry $entry): bool => $entry->level === 'info');
    Log::assertNothingLogged()->assertWasNotForgotten();
    Log::info('x');
    Log::channel('x');

    Log::assertLogged('not a closure');
    Log::assertLoged(fn (): bool => true);
    Log::assertLoggedTimes(fn (): bool => true, 'once');
    Log::assertNotLogged(fn (string $entry): bool => true);
}
