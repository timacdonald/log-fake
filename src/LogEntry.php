<?php

namespace TiMacDonald\Log;

use Stringable;

class LogEntry
{
    public function __construct(
        public mixed $level,
        public string|Stringable $message,
        /** @var array<array-key, mixed> */
        public array $context,
        public int $timesChannelHasBeenForgottenAtTimeOfWritingLog
    ) {
        //
    }
}
