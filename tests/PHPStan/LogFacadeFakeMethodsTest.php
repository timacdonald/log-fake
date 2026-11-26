<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use Illuminate\Log\Logger;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Log;
use LogicException;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;
use ReflectionClass;
use ReflectionMethod;
use TiMacDonald\Log\ChannelFake;
use TiMacDonald\Log\LogFake;
use TiMacDonald\Log\PHPStan\LogFacadeFakeMethodsExtension;

class LogFacadeFakeMethodsTest extends PHPStanTestCase
{
    public function test_it_declares_the_fake_only_methods_on_the_log_facade(): void
    {
        $facade = self::reflectionProvider()->getClass(Log::class);

        foreach (self::expectedOwners() as $name => $owner) {
            self::assertTrue(self::extension()->hasMethod($facade, $name), "Expected [{$name}] to be declared.");

            $method = self::extension()->getMethod($facade, $name);
            $fakeMethod = self::reflectionProvider()->getClass($owner)->getNativeMethod($name);

            self::assertSame($name, $method->getName());
            self::assertSame($facade, $method->getDeclaringClass());
            self::assertTrue($method->isStatic());
            self::assertTrue($method->isPublic());
            self::assertFalse($method->isPrivate());
            self::assertTrue($method->isFinal()->no());
            self::assertTrue($method->isInternal()->no());
            self::assertTrue($method->hasSideEffects()->yes());
            self::assertSame($method, $method->getPrototype());
            self::assertSame($fakeMethod->getVariants(), $method->getVariants(), "Unexpected owner for [{$name}].");
            self::assertSame($fakeMethod->getDocComment(), $method->getDocComment());
            self::assertTrue($method->isDeprecated()->no());
            self::assertNull($method->getDeprecatedDescription());
            self::assertNull($method->getThrowType());
        }
    }

    public function test_method_names_are_case_insensitive(): void
    {
        $facade = self::reflectionProvider()->getClass(Log::class);

        self::assertTrue(self::extension()->hasMethod($facade, 'ASSERTLOGGED'));
        self::assertSame('assertLogged', self::extension()->getMethod($facade, 'ASSERTLOGGED')->getName());
    }

    public function test_it_ignores_real_logger_and_unknown_methods(): void
    {
        $facade = self::reflectionProvider()->getClass(Log::class);

        foreach (['info', 'log', 'channel', 'stack', 'build', 'shareContext', 'getChannels', 'withContext', 'withoutContext', 'write', 'listen', 'getLogger', 'getEventDispatcher', 'setEventDispatcher', '__call', '__construct', 'bind', 'logged', 'currentContext', 'assertLoged'] as $name) {
            self::assertFalse(self::extension()->hasMethod($facade, $name), "Expected [{$name}] not to be declared.");
        }
    }

    public function test_it_only_applies_to_the_log_facade(): void
    {
        foreach ([LogFake::class, ChannelFake::class, LogManager::class, Logger::class] as $class) {
            self::assertFalse(self::extension()->hasMethod(self::reflectionProvider()->getClass($class), 'assertLogged'));
        }
    }

    public function test_get_method_throws_for_unknown_methods(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Not a log fake method: assertLoged');

        self::extension()->getMethod(self::reflectionProvider()->getClass(Log::class), 'assertLoged');
    }

    public function test_the_all_methods_fixture_calls_every_fake_only_method(): void
    {
        $fixture = (string) file_get_contents(__DIR__.'/data/all-methods.php');

        foreach (array_keys(self::expectedOwners()) as $name) {
            self::assertStringContainsString("Log::{$name}(", $fixture);
        }
    }

    public function test_the_spec_list_of_methods_is_declared(): void
    {
        self::assertSame([
            'dump', 'dd', 'assertLogged', 'assertLoggedTimes', 'assertNotLogged', 'assertNothingLogged', 'assertWasForgotten',
            'assertWasForgottenTimes', 'assertWasNotForgotten', 'assertCurrentContext', 'logs', 'forget', 'remember',
            'isCurrentlyForgotten', 'clearContext', 'dumpAll', 'ddAll', 'assertChannelIsCurrentlyForgotten',
            'assertHasSharedContext', 'allLogs',
        ], array_keys(self::expectedOwners()));
    }

    /**
     * Derived independently with PHP's runtime reflection, mirroring how the
     * facade call is resolved at runtime.
     *
     * @return array<string, class-string>
     */
    private static function expectedOwners(): array
    {
        $real = array_map(
            strtolower(...),
            array_merge(get_class_methods(LogManager::class), get_class_methods(Logger::class)),
        );

        $owners = [];

        foreach ([ChannelFake::class, LogFake::class] as $fake) {
            foreach ((new ReflectionClass($fake))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if (! $method->isStatic() && ! str_starts_with($method->name, '__') && ! in_array(strtolower($method->name), $real, true)) {
                    $owners[$method->name] = $fake;
                }
            }
        }

        return $owners;
    }

    private static function extension(): LogFacadeFakeMethodsExtension
    {
        return self::getContainer()->getByType(LogFacadeFakeMethodsExtension::class);
    }

    private static function reflectionProvider(): ReflectionProvider
    {
        return self::getContainer()->getByType(ReflectionProvider::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__.'/../../extension.neon'];
    }
}
