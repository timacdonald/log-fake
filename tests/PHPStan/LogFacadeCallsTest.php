<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use PHPStan\Rules\Methods\CallStaticMethodsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CallStaticMethodsRule>
 */
class LogFacadeCallsTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        // Test-only use of PHPStan's own rule; it is the rule users hit.
        return self::getContainer()->getByType(CallStaticMethodsRule::class); // @phpstan-ignore phpstanApi.classConstant
    }

    public function test_calls(): void
    {
        $this->analyse([__DIR__.'/data/calls.php'], [
            [
                'Parameter #1 $callback of static method Illuminate\Support\Facades\Log::assertLogged() expects Closure(TiMacDonald\Log\LogEntry): bool, \'not a closure\' given.',
                17,
            ],
            [
                'Call to an undefined static method Illuminate\Support\Facades\Log::assertLoged().',
                18,
            ],
            [
                'Parameter #2 $times of static method Illuminate\Support\Facades\Log::assertLoggedTimes() expects int, string given.',
                19,
            ],
            [
                'Parameter #1 $callback of static method Illuminate\Support\Facades\Log::assertNotLogged() expects Closure(TiMacDonald\Log\LogEntry): bool, Closure(string): true given.',
                20,
            ],
        ]);
    }

    public function test_error_identifiers(): void
    {
        $identifiers = [];

        foreach ($this->gatherAnalyserErrors([__DIR__.'/data/calls.php']) as $error) {
            $identifiers[] = [$error->getLine(), $error->getIdentifier()];
        }

        self::assertSame([
            [17, 'argument.type'],
            [18, 'staticMethod.notFound'],
            [19, 'argument.type'],
            [20, 'argument.type'],
        ], $identifiers);
    }

    public function test_every_fake_only_method_can_be_called(): void
    {
        $this->analyse([__DIR__.'/data/all-methods.php'], []);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__.'/../../extension.neon'];
    }
}
