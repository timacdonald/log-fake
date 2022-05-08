<?php

declare(strict_types=1);

namespace TiMacDonald\Log;

use Closure;
use RuntimeException;

/**
 * @no-named-arguments
 */
class StackFake extends ChannelFake
{
    /**
     * @link https://github.com/timacdonald/log-fake#assertcurrentcontext Documentation
     * @param (Closure(array<array-key, mixed>): bool)|array<array-key, mixed> $context
     */
    public function assertCurrentContext(Closure|array $context, ?string $message = null): StackFake
    {
        throw new RuntimeException('Cannot call [Log::stack(...)->assertCurrentContext(...)] as stack contexts are reset each time they are resolved from the LogManager.');
    }

    public function assertWasForgotten(?string $message = null): ChannelFake
    {
        throw new RuntimeException('Cannot call [Log::stack(...)->assertWasForgotten(...)] as stack is not able to be forgotten.');
    }

    public function assertWasForgottenTimes(int $times, ?string $message = null): ChannelFake
    {
        throw new RuntimeException('Cannot call [Log::stack(...)->assertWasForgottenTimes(...)] as stack is not able to be forgotten.');
    }

    public function assertWasNotForgotten(?string $message = null): ChannelFake
    {
        throw new RuntimeException('Cannot call [Log::stack(...)->assertWasNotForgotten(...)] as stack is not able to be forgotten.');
    }
}
