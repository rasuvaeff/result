<?php

declare(strict_types=1);

namespace Rasuvaeff\Result\Tests;

use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Gen;
use Rasuvaeff\PropertyTesting\Property;
use Rasuvaeff\Result\Option;
use Rasuvaeff\Result\Result;
use Rasuvaeff\Result\UnwrapException;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Test;

#[Test]
#[Covers(Option::class)]
#[Covers(Result::class)]
#[Covers(UnwrapException::class)]
final class OptionTest
{
    public function someStoresValue(): void
    {
        $option = Option::some(value: 42);

        Assert::true($option->isSome());
        Assert::false($option->isNone());
        Assert::same($option->unwrap(), 42);
    }

    public function someCanStoreNull(): void
    {
        $option = Option::some(value: null);

        Assert::true($option->isSome());
        Assert::null($option->unwrap());
    }

    public function noneHasNoValue(): void
    {
        $option = Option::none();

        Assert::false($option->isSome());
        Assert::true($option->isNone());
    }

    #[DataProvider('fromNullableProvider')]
    public function fromNullableConvertsNullOnly(mixed $value, bool $some): void
    {
        $option = Option::fromNullable(value: $value);

        Assert::same($option->isSome(), $some);
    }

    public static function fromNullableProvider(): iterable
    {
        yield 'null' => [null, false];
        yield 'zero' => [0, true];
        yield 'empty string' => ['', true];
        yield 'false' => [false, true];
    }

    public function unwrapThrowsOnNone(): void
    {
        Expect::exception(UnwrapException::class)->withMessageContaining('Cannot unwrap a None option');

        Option::none()->unwrap();
    }

    public function unwrapOrReturnsValueForSome(): void
    {
        $option = Option::some(value: 'actual');

        Assert::same($option->unwrapOr(default: 'fallback'), 'actual');
    }

    public function unwrapOrReturnsDefaultForNone(): void
    {
        $option = Option::none();

        Assert::same($option->unwrapOr(default: 'fallback'), 'fallback');
    }

    public function mapTransformsSomeValue(): void
    {
        $option = Option::some(value: 21)
            ->map(fn(int $value): int => $value * 2);

        Assert::same($option->unwrap(), 42);
    }

    public function mapKeepsNone(): void
    {
        $option = Option::none()
            ->map(fn(int $value): int => $value * 2);

        Assert::true($option->isNone());
    }

    public function filterKeepsMatchingSome(): void
    {
        $option = Option::some(value: 42)
            ->filter(fn(int $value): bool => $value > 40);

        Assert::true($option->isSome());
        Assert::same($option->unwrap(), 42);
    }

    public function filterDropsNonMatchingSome(): void
    {
        $option = Option::some(value: 42)
            ->filter(fn(int $value): bool => $value < 40);

        Assert::true($option->isNone());
    }

    public function filterKeepsNone(): void
    {
        $option = Option::none()
            ->filter(fn(int $value): bool => $value > 40);

        Assert::true($option->isNone());
    }

    public function toResultConvertsSomeToOk(): void
    {
        $result = Option::some(value: 42)->toResult(error: 'missing');

        Assert::true($result->isOk());
        Assert::same($result->unwrap(), 42);
    }

    public function toResultConvertsNoneToErr(): void
    {
        $result = Option::none()->toResult(error: 'missing');

        Assert::true($result->isErr());
        Assert::same($result->error(), 'missing');
    }

    #[Property(runs: 300)]
    public function fromNullableIsSomeExactlyWhenNotNull(?int $value): void
    {
        Assert::same(Option::fromNullable(value: $value)->isSome(), $value !== null);
    }

    /** @return array<string, ArbitraryInterface> */
    private function fromNullableIsSomeExactlyWhenNotNullGenerators(): array
    {
        return ['value' => Gen::nullable(Gen::int())];
    }

    #[Property(runs: 300)]
    public function mapWithIdentityKeepsSomeValue(int $value): void
    {
        Assert::same(Option::some(value: $value)->map(fn(int $v): int => $v)->unwrap(), $value);
    }

    /** @return array<string, ArbitraryInterface> */
    private function mapWithIdentityKeepsSomeValueGenerators(): array
    {
        return ['value' => Gen::int()];
    }

    #[Property(runs: 300)]
    public function mapComposesLikeOneStep(int $value, int $add, int $mul): void
    {
        $twoStep = Option::some(value: $value)
            ->map(fn(int $v): int => $v + $add)
            ->map(fn(int $v): int => $v * $mul)
            ->unwrap();
        $oneStep = Option::some(value: $value)
            ->map(fn(int $v): int => ($v + $add) * $mul)
            ->unwrap();

        Assert::same($twoStep, $oneStep);
    }

    /** @return array<string, ArbitraryInterface> */
    private function mapComposesLikeOneStepGenerators(): array
    {
        return [
            'value' => Gen::intBetween(-1_000_000, 1_000_000),
            'add' => Gen::intBetween(-1_000_000, 1_000_000),
            'mul' => Gen::intBetween(-1_000, 1_000),
        ];
    }

    #[Property(runs: 300)]
    public function filterKeepsSomeExactlyWhenPredicateHolds(int $value, bool $keep): void
    {
        $option = Option::some(value: $value)->filter(fn(int $v): bool => $keep);

        Assert::same($option->isSome(), $keep);
    }

    /** @return array<string, ArbitraryInterface> */
    private function filterKeepsSomeExactlyWhenPredicateHoldsGenerators(): array
    {
        return [
            'value' => Gen::int(),
            'keep' => Gen::bool(),
        ];
    }

    #[Property(runs: 300)]
    public function toResultMirrorsPresence(bool $present, int $value, int $error): void
    {
        $option = $present ? Option::some(value: $value) : Option::none();
        $result = $option->toResult(error: $error);

        Assert::same($result->isOk(), $present);
        Assert::same($present ? $result->value() : $result->error(), $present ? $value : $error);
    }

    /** @return array<string, ArbitraryInterface> */
    private function toResultMirrorsPresenceGenerators(): array
    {
        return [
            'present' => Gen::bool(),
            'value' => Gen::int(),
            'error' => Gen::int(),
        ];
    }
}
