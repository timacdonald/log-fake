<?php

declare(strict_types=1);

namespace TiMacDonald\Log\PHPStan;

use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

/**
 * Exposes an instance method of a log fake as a static method of the Log facade.
 *
 * @internal
 */
final class StaticFacadeMethodReflection implements MethodReflection
{
    public function __construct(
        private readonly ClassReflection $facade,
        private readonly MethodReflection $fakeMethod,
    ) {
        //
    }

    public function getDeclaringClass(): ClassReflection
    {
        return $this->facade;
    }

    public function isStatic(): bool
    {
        return true;
    }

    public function isPrivate(): bool
    {
        return false;
    }

    public function isPublic(): bool
    {
        return true;
    }

    public function getDocComment(): ?string
    {
        return $this->fakeMethod->getDocComment();
    }

    public function getName(): string
    {
        return $this->fakeMethod->getName();
    }

    public function getPrototype(): ClassMemberReflection
    {
        return $this;
    }

    public function getVariants(): array
    {
        return $this->fakeMethod->getVariants();
    }

    public function isDeprecated(): TrinaryLogic
    {
        return $this->fakeMethod->isDeprecated();
    }

    public function getDeprecatedDescription(): ?string
    {
        return $this->fakeMethod->getDeprecatedDescription();
    }

    public function isFinal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function isInternal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function getThrowType(): ?Type
    {
        return $this->fakeMethod->getThrowType();
    }

    public function hasSideEffects(): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
}
