<?php

declare(strict_types=1);

namespace TiMacDonald\Log;

use Illuminate\Contracts\Support\Arrayable;
use Stringable;

/**
 * @no-named-arguments
 * @implements Arrayable<string, mixed>
 */
class LogEntry implements Arrayable
{
    public function __construct(
        public mixed $level,
        public string|Stringable $message,
        /** @var array<array-key, mixed> */
        public array $context,
        public int $timesChannelHasBeenForgottenAtTimeOfWritingLog,
        public string $channel
    ) {
        //
    }

    public function toArray(): array
    {
        return [
            'level' => $this->level,
            'message' => $this->message,
            'context' => $this->context,
            'times_channel_has_been_forgotten_at_time_of_writing_log' => $this->timesChannelHasBeenForgottenAtTimeOfWritingLog,
            'channel' => $this->channel,
        ];
    }
}
