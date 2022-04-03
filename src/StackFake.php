<?php

namespace TiMacDonald\Log;

use RuntimeException;

/**
 * @no-named-arguments
 */
class StackFake extends ChannelFake
{
    /**
     * @param array<string, mixed> $context
     */
    public function assertCurrentContext(array $context): StackFake
    {
        throw new RuntimeException('Cannot call [Log::stack(...)->assertCurrentContext(...)] as stack contexts are reset each time they are resolved from the LogManager. Instead utilise [Log::stack(...)->assertHadContext(...)].');
    }
}
