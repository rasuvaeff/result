<?php

declare(strict_types=1);

namespace Rasuvaeff\Result\Tests;

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
}
