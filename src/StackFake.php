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
     * @param Closure|array<string, mixed> $context
     */
    public function assertCurrentContext(Closure|array $context): StackFake
    {
        throw new RuntimeException('Cannot call [Log::stack(...)->assertCurrentContext(...)] as stack contexts are reset each time they are resolved from the LogManager. Instead utilise [Log::stack(...)->assertHadContext(...)].');
    }
}
