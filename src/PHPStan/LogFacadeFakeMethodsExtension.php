<?php

declare(strict_types=1);

namespace TiMacDonald\Log\PHPStan;

use Illuminate\Log\Logger;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Log;
use LogicException;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ReflectionProvider;
use TiMacDonald\Log\ChannelFake;
use TiMacDonald\Log\LogFake;

/**
 * Declares the fake-only methods of LogFake / ChannelFake as static methods
 * of the Log facade, with the fake's real signatures and return types.
 *
 * @internal
 */
final class LogFacadeFakeMethodsExtension implements MethodsClassReflectionExtension
{
    /**
     * @var array<lowercase-string, class-string>|null
     */
    private ?array $owners = null;

    public function __construct(private readonly ReflectionProvider $reflectionProvider)
    {
        //
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        return $classReflection->getName() === Log::class && $this->owner($methodName) !== null;
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        $owner = $this->owner($methodName) ?? throw new LogicException("Not a log fake method: {$methodName}");

        return new StaticFacadeMethodReflection(
            $classReflection,
            $this->reflectionProvider->getClass($owner)->getNativeMethod($methodName),
        );
    }

    /**
     * @return class-string|null
     */
    private function owner(string $methodName): ?string
    {
        return $this->owners()[strtolower($methodName)] ?? null;
    }

    /**
     * @infection-ignore-all
     *
     * @return array<lowercase-string, class-string>
     */
    private function owners(): array
    {
        return $this->owners ??= $this->resolveOwners();
    }

    /**
     * Maps each fake-only method name to the fake that handles it at runtime.
     *
     * @return array<lowercase-string, class-string>
     */
    private function resolveOwners(): array
    {
        $owners = [];

        // The "real" logger behind the facade: the LogManager root and the
        // Logger channel it forwards unknown calls to via LogManager::__call().
        $realLoggers = [
            $this->reflectionProvider->getClass(LogManager::class),
            $this->reflectionProvider->getClass(Logger::class),
        ];

        // LogFake last, so it wins: the facade root is the LogFake and only
        // the methods it lacks are forwarded to the default ChannelFake.
        foreach ([ChannelFake::class, LogFake::class] as $fake) {
            foreach ($this->reflectionProvider->getClass($fake)->getNativeReflection()->getMethods() as $method) {
                $name = $method->getName();

                if (! $method->isPublic() || $method->isStatic() || str_starts_with($name, '__') || $this->isRealLoggerMethod($realLoggers, $name)) {
                    continue;
                }

                $owners[strtolower($name)] = $fake;
            }
        }

        return $owners;
    }

    /**
     * @param  list<ClassReflection>  $realLoggers
     */
    private function isRealLoggerMethod(array $realLoggers, string $methodName): bool
    {
        foreach ($realLoggers as $realLogger) {
            if ($realLogger->hasNativeMethod($methodName)) {
                return true;
            }
        }

        return false;
    }
}
