<?php

namespace TiMacDonald\Log;

use Illuminate\Support\Collection;
use RuntimeException;
use stdClass;

class StackFake extends ChannelFake
{
    /**
     * @var array{'_': stdClass}
     */
    private array $sentinalContext;

    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->sentinalContext = ['_' => new stdClass()];
    }

    /**
     * @param array<string, mixed> $context
     */
    public function assertCurrentContext(array $context): void
    {
        throw new RuntimeException('Cannot call [Log::stack(...)->assertCurrentContext(...)] as stack contexts are reset each time they are resolved from the LogManager. Instead utilise [Log::stack(...)->assertHadContext(...)].');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function allContextInstances(): Collection
    {
        return parent::allContextInstances()
            ->filter(fn (array $value): bool => $this->isNotSentinalContext($value))
            ->values();
    }

    protected function currentContext(): array
    {
        $context = parent::currentContext();

        return $this->isNotSentinalContext($context)
            ? $context
            : [];
    }

    /**
     * @internal
     */
    public function clearContext(): StackFake
    {
        $this->context[] = $this->sentinalContext;

        return $this;
    }

    /**
     * @param array<array-key, mixed> $context
     */
    private function isNotSentinalContext(array $context): bool
    {
        return $this->sentinalContext !== $context;
    }
}
