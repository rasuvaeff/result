<?php

declare(strict_types=1);

namespace Rasuvaeff\Result\Tests;

use Rasuvaeff\Result\Result;
use Rasuvaeff\Result\UnwrapException;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Test;

#[Test]
#[Covers(Result::class)]
#[Covers(UnwrapException::class)]
final class ResultTest
{
    public function okStoresValue(): void
    {
        $result = Result::ok(value: 42);

        Assert::true($result->isOk());
        Assert::false($result->isErr());
        Assert::same($result->unwrap(), 42);
        Assert::same($result->value(), 42);
        Assert::null($result->error());
    }

    public function okCanStoreNull(): void
    {
        $result = Result::ok(value: null);

        Assert::true($result->isOk());
        Assert::null($result->unwrap());
        Assert::null($result->value());
    }

    public function errStoresError(): void
    {
        $result = Result::err(error: 'failed');

        Assert::false($result->isOk());
        Assert::true($result->isErr());
        Assert::same($result->error(), 'failed');
        Assert::null($result->value());
    }

    public function errCanStoreNull(): void
    {
        $result = Result::err(error: null);

        Assert::true($result->isErr());
        Assert::null($result->error());
    }

    public function unwrapThrowsOnErr(): void
    {
        Expect::exception(UnwrapException::class)->withMessageContaining('Cannot unwrap an Err result');

        Result::err(error: 'failed')->unwrap();
    }

    public function unwrapOrReturnsValueForOk(): void
    {
        $result = Result::ok(value: 'actual');

        Assert::same($result->unwrapOr(default: 'fallback'), 'actual');
    }

    public function unwrapOrReturnsDefaultForErr(): void
    {
        $result = Result::err(error: 'failed');

        Assert::same($result->unwrapOr(default: 'fallback'), 'fallback');
    }

    public function mapTransformsOkValue(): void
    {
        $result = Result::ok(value: 21)
            ->map(fn(int $value): int => $value * 2);

        Assert::same($result->unwrap(), 42);
    }

    public function mapKeepsErrUntouched(): void
    {
        $result = Result::err(error: 'failed')
            ->map(fn(int $value): int => $value * 2);

        Assert::true($result->isErr());
        Assert::same($result->error(), 'failed');
    }

    public function mapErrTransformsError(): void
    {
        $result = Result::err(error: 'failed')
            ->mapErr(fn(string $error): string => strtoupper($error));

        Assert::same($result->error(), 'FAILED');
    }

    public function mapErrKeepsOkUntouched(): void
    {
        $result = Result::ok(value: 42)
            ->mapErr(fn(string $error): string => strtoupper($error));

        Assert::true($result->isOk());
        Assert::same($result->unwrap(), 42);
    }

    public function flatMapChainsOkValue(): void
    {
        $result = Result::ok(value: 21)
            ->flatMap(fn(int $value): Result => Result::ok(value: $value * 2));

        Assert::same($result->unwrap(), 42);
    }

    public function flatMapKeepsErrUntouched(): void
    {
        $result = Result::err(error: 'failed')
            ->flatMap(fn(int $value): Result => Result::ok(value: $value * 2));

        Assert::true($result->isErr());
        Assert::same($result->error(), 'failed');
    }

    #[DataProvider('fromThrowableProvider')]
    public function fromThrowableConvertsClosureToResult(bool $throws, bool $ok): void
    {
        $result = Result::fromThrowable(
            fn(): string => $throws ? throw new \RuntimeException(message: 'boom') : 'done',
        );

        Assert::same($result->isOk(), $ok);
    }

    public static function fromThrowableProvider(): iterable
    {
        yield 'successful closure' => [false, true];
        yield 'throwing closure' => [true, false];
    }

    public function fromThrowableStoresException(): void
    {
        $result = Result::fromThrowable(
            fn(): string => throw new \RuntimeException(message: 'boom'),
        );

        Assert::instanceOf($result->error(), \RuntimeException::class);
    }

    public function matchReturnsOkBranch(): void
    {
        $result = Result::ok(value: 21)->match(
            ok: fn(int $value): int => $value * 2,
            err: fn(string $error): int => strlen($error),
        );

        Assert::same($result, 42);
    }

    public function matchReturnsErrBranch(): void
    {
        $result = Result::err(error: 'failed')->match(
            ok: fn(int $value): int => $value * 2,
            err: fn(string $error): int => strlen($error),
        );

        Assert::same($result, 6);
    }
}
