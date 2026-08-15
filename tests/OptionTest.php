<?php

declare(strict_types=1);

namespace Rasuvaeff\Result\Tests;

use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Classify;
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
    public static function fromNullableIsSomeExactlyWhenNotNullGenerators(): array
    {
        return ['value' => Gen::nullable(Gen::int())];
    }

    /** @return iterable<string, array{?int}> */
    public static function fromNullableIsSomeExactlyWhenNotNullExamples(): iterable
    {
        // 0 and null are the pair a nullable API most often conflates.
        yield 'null' => [null];
        yield 'zero' => [0];
        yield 'integer floor' => [PHP_INT_MIN];
        yield 'integer ceiling' => [PHP_INT_MAX];
    }

    #[Property(runs: 300)]
    public function mapWithIdentityKeepsSomeValue(int $value): void
    {
        Assert::same(Option::some(value: $value)->map(fn(int $v): int => $v)->unwrap(), $value);
    }

    /** @return array<string, ArbitraryInterface> */
    public static function mapWithIdentityKeepsSomeValueGenerators(): array
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
    public static function mapComposesLikeOneStepGenerators(): array
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
    public static function filterKeepsSomeExactlyWhenPredicateHoldsGenerators(): array
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
    public static function toResultMirrorsPresenceGenerators(): array
    {
        return [
            'present' => Gen::bool(),
            'value' => Gen::int(),
            'error' => Gen::int(),
        ];
    }

    #[Property(runs: 300)]
    public function unwrapOrFallsBackExactlyOnNone(bool $present, int $value, int $default): void
    {
        $option = $present ? Option::some(value: $value) : Option::none();

        Classify::cover($present, 'some', 30.0);
        Classify::cover(!$present, 'none', 30.0);

        Assert::same($option->unwrapOr(default: $default), $present ? $value : $default);
    }

    /** @return array<string, ArbitraryInterface> */
    public static function unwrapOrFallsBackExactlyOnNoneGenerators(): array
    {
        return [
            'present' => Gen::bool(),
            'value' => Gen::int(),
            'default' => Gen::int(),
        ];
    }

    /** @return iterable<string, array{bool, int, int}> */
    public static function unwrapOrFallsBackExactlyOnNoneExamples(): iterable
    {
        // A Some carrying the same value as the default: the two branches
        // become indistinguishable by their result, so only the branch taken
        // can be wrong here.
        yield 'some equal to the default' => [true, 7, 7];
        yield 'none with a zero default' => [false, 7, 0];
    }

    #[Property(runs: 300)]
    public function filterIsIdempotent(bool $present, int $value, int $threshold): void
    {
        $option = $present ? Option::some(value: $value) : Option::none();
        $predicate = static fn(int $v): bool => $v > $threshold;

        $once = $option->filter($predicate);
        $twice = $once->filter($predicate);

        Classify::cover($present && $value > $threshold, 'kept', 20.0);
        Classify::cover($present && $value <= $threshold, 'dropped by the predicate', 20.0);
        Classify::when(!$present, 'already none');

        Assert::same($twice->isSome(), $once->isSome());
        Assert::same($twice->unwrapOr(default: null), $once->unwrapOr(default: null));
    }

    /** @return array<string, ArbitraryInterface> */
    public static function filterIsIdempotentGenerators(): array
    {
        return [
            'present' => Gen::bool(),
            // A narrow, shared range keeps both sides of the threshold
            // reachable — with the full int range the predicate would answer
            // the same way in almost every run.
            'value' => Gen::intBetween(-100, 100),
            'threshold' => Gen::intBetween(-100, 100),
        ];
    }

    #[Property(runs: 300)]
    public function mapNeverRevivesNone(bool $present, int $value, int $delta): void
    {
        $option = $present ? Option::some(value: $value) : Option::none();

        Classify::cover($present, 'some', 30.0);
        Classify::cover(!$present, 'none', 30.0);

        $mapped = $option->map(static fn(int $v): int => $v + $delta);

        Assert::same($mapped->isSome(), $present);
        Assert::same($mapped->unwrapOr(default: null), $present ? $value + $delta : null);
    }

    /** @return array<string, ArbitraryInterface> */
    public static function mapNeverRevivesNoneGenerators(): array
    {
        return [
            'present' => Gen::bool(),
            'value' => Gen::intBetween(-1_000_000, 1_000_000),
            'delta' => Gen::intBetween(-1_000_000, 1_000_000),
        ];
    }

    #[Property(runs: 300)]
    public function toResultRoundTripsThroughFromNullable(?int $value): void
    {
        $option = Option::fromNullable(value: $value);

        Classify::cover($value === null, 'null input', 20.0);
        Classify::cover($value !== null, 'non-null input', 20.0);

        Assert::same($option->toResult(error: 'missing')->value(), $value);
    }

    /** @return array<string, ArbitraryInterface> */
    public static function toResultRoundTripsThroughFromNullableGenerators(): array
    {
        return ['value' => Gen::nullable(Gen::int())];
    }
}
