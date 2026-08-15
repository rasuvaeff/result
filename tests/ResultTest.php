<?php

declare(strict_types=1);

namespace Rasuvaeff\Result\Tests;

use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Classify;
use Rasuvaeff\PropertyTesting\Gen;
use Rasuvaeff\PropertyTesting\Property;
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

    #[Property(runs: 300)]
    public function mapWithIdentityKeepsValue(int $value): void
    {
        Assert::same(Result::ok(value: $value)->map(fn(int $v): int => $v)->value(), $value);
    }

    /** @return array<string, ArbitraryInterface> */
    public static function mapWithIdentityKeepsValueGenerators(): array
    {
        return ['value' => Gen::int()];
    }

    #[Property(runs: 300)]
    public function mapComposesLikeOneStep(int $value, int $add, int $mul): void
    {
        $twoStep = Result::ok(value: $value)
            ->map(fn(int $v): int => $v + $add)
            ->map(fn(int $v): int => $v * $mul)
            ->value();
        $oneStep = Result::ok(value: $value)
            ->map(fn(int $v): int => ($v + $add) * $mul)
            ->value();

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
    public function mapNeverTouchesErr(int $error, int $add): void
    {
        $result = Result::err(error: $error)->map(fn(int $v): int => $v + $add);

        Assert::true($result->isErr());
        Assert::same($result->error(), $error);
    }

    /** @return array<string, ArbitraryInterface> */
    public static function mapNeverTouchesErrGenerators(): array
    {
        return [
            'error' => Gen::int(),
            'add' => Gen::int(),
        ];
    }

    #[Property(runs: 300)]
    public function flatMapOkObeysLeftIdentity(int $value, int $delta): void
    {
        $viaFlatMap = Result::ok(value: $value)
            ->flatMap(fn(int $v): Result => Result::ok(value: $v + $delta))
            ->value();

        Assert::same($viaFlatMap, $value + $delta);
    }

    /** @return array<string, ArbitraryInterface> */
    public static function flatMapOkObeysLeftIdentityGenerators(): array
    {
        return [
            'value' => Gen::intBetween(-1_000_000, 1_000_000),
            'delta' => Gen::intBetween(-1_000_000, 1_000_000),
        ];
    }

    #[Property(runs: 300)]
    public function isOkIsAlwaysOppositeOfIsErr(bool $ok, int $payload): void
    {
        $result = $ok ? Result::ok(value: $payload) : Result::err(error: $payload);

        Assert::same($result->isOk(), !$result->isErr());
        Assert::same($result->isOk(), $ok);
    }

    /** @return array<string, ArbitraryInterface> */
    public static function isOkIsAlwaysOppositeOfIsErrGenerators(): array
    {
        return [
            'ok' => Gen::bool(),
            'payload' => Gen::int(),
        ];
    }

    #[Property(runs: 300)]
    public function unwrapOrPicksValueOrDefaultByBranch(bool $ok, int $payload, int $default): void
    {
        $result = $ok ? Result::ok(value: $payload) : Result::err(error: $payload);

        Assert::same($result->unwrapOr(default: $default), $ok ? $payload : $default);
    }

    /** @return array<string, ArbitraryInterface> */
    public static function unwrapOrPicksValueOrDefaultByBranchGenerators(): array
    {
        return [
            'ok' => Gen::bool(),
            'payload' => Gen::int(),
            'default' => Gen::int(),
        ];
    }

    #[Property(runs: 300)]
    public function flatMapObeysRightIdentity(bool $ok, int $payload): void
    {
        $result = $ok ? Result::ok(value: $payload) : Result::err(error: $payload);

        Classify::cover($ok, 'ok', 30.0);
        Classify::cover(!$ok, 'err', 30.0);

        $wrapped = $result->flatMap(static fn(int $v): Result => Result::ok(value: $v));

        Assert::same($wrapped->isOk(), $result->isOk());
        Assert::same($wrapped->value(), $result->value());
        Assert::same($wrapped->error(), $result->error());
    }

    /** @return array<string, ArbitraryInterface> */
    public static function flatMapObeysRightIdentityGenerators(): array
    {
        return [
            'ok' => Gen::bool(),
            'payload' => Gen::int(),
        ];
    }

    /** @return iterable<string, array{bool, int}> */
    public static function flatMapObeysRightIdentityExamples(): iterable
    {
        yield 'ok carrying zero' => [true, 0];
        yield 'err carrying zero' => [false, 0];
        yield 'ok at the integer ceiling' => [true, PHP_INT_MAX];
        yield 'err at the integer floor' => [false, PHP_INT_MIN];
    }

    #[Property(runs: 300)]
    public function flatMapIsAssociative(bool $ok, int $payload, int $add, int $mul): void
    {
        $result = $ok ? Result::ok(value: $payload) : Result::err(error: $payload);
        $f = static fn(int $v): Result => Result::ok(value: $v + $add);
        $g = static fn(int $v): Result => Result::ok(value: $v * $mul);

        Classify::cover($ok, 'ok', 30.0);
        Classify::cover(!$ok, 'err short-circuits both spellings', 30.0);

        $leftAssociated = $result->flatMap($f)->flatMap($g);
        $rightAssociated = $result->flatMap(static fn(int $v): Result => $f($v)->flatMap($g));

        Assert::same($leftAssociated->value(), $rightAssociated->value());
        Assert::same($leftAssociated->error(), $rightAssociated->error());
    }

    /** @return array<string, ArbitraryInterface> */
    public static function flatMapIsAssociativeGenerators(): array
    {
        // Bounded so that (value + add) * mul stays an int: PHP promotes an
        // overflowing product to float, and float equality would make the law
        // a statement about IEEE rounding instead of about flatMap.
        return [
            'ok' => Gen::bool(),
            'payload' => Gen::intBetween(-1_000_000, 1_000_000),
            'add' => Gen::intBetween(-1_000_000, 1_000_000),
            'mul' => Gen::intBetween(-1_000, 1_000),
        ];
    }

    #[Property(runs: 300)]
    public function mapErrIsTheMirrorImageOfMap(bool $ok, int $payload, int $delta): void
    {
        $result = $ok ? Result::ok(value: $payload) : Result::err(error: $payload);

        Classify::cover($ok, 'ok', 30.0);
        Classify::cover(!$ok, 'err', 30.0);

        $mapped = $result->mapErr(static fn(int $e): int => $e + $delta);

        Assert::same($mapped->isOk(), $ok);
        Assert::same($mapped->value(), $ok ? $payload : null);
        Assert::same($mapped->error(), $ok ? null : $payload + $delta);
    }

    /** @return array<string, ArbitraryInterface> */
    public static function mapErrIsTheMirrorImageOfMapGenerators(): array
    {
        return [
            'ok' => Gen::bool(),
            'payload' => Gen::intBetween(-1_000_000, 1_000_000),
            'delta' => Gen::intBetween(-1_000_000, 1_000_000),
        ];
    }

    #[Property(runs: 300)]
    public function matchRunsExactlyOneArm(bool $ok, int $payload): void
    {
        $result = $ok ? Result::ok(value: $payload) : Result::err(error: $payload);
        $okCalls = 0;
        $errCalls = 0;

        Classify::cover($ok, 'ok arm', 30.0);
        Classify::cover(!$ok, 'err arm', 30.0);

        $returned = $result->match(
            ok: function (int $v) use (&$okCalls): string {
                ++$okCalls;

                return "ok:{$v}";
            },
            err: function (int $e) use (&$errCalls): string {
                ++$errCalls;

                return "err:{$e}";
            },
        );

        Assert::same($okCalls + $errCalls, 1);
        Assert::same($returned, $ok ? "ok:{$payload}" : "err:{$payload}");
    }

    /** @return array<string, ArbitraryInterface> */
    public static function matchRunsExactlyOneArmGenerators(): array
    {
        return [
            'ok' => Gen::bool(),
            'payload' => Gen::int(),
        ];
    }

    #[Property(runs: 200)]
    public function fromThrowableIsErrExactlyWhenTheClosureThrows(bool $throws, int $payload): void
    {
        Classify::cover($throws, 'closure throws', 30.0);
        Classify::cover(!$throws, 'closure returns', 30.0);

        $result = Result::fromThrowable(
            static fn(): int => $throws
                ? throw new \RuntimeException(message: "boom:{$payload}")
                : $payload,
        );

        Assert::same($result->isErr(), $throws);

        // match() is the only way to read the payload back without asking
        // psalm to trust a mixed return: each arm is typed by the branch.
        Assert::same(
            $result->match(
                ok: static fn(int $value): string => "returned:{$value}",
                err: static fn(\Throwable $error): string => $error->getMessage(),
            ),
            $throws ? "boom:{$payload}" : "returned:{$payload}",
        );
    }

    /** @return array<string, ArbitraryInterface> */
    public static function fromThrowableIsErrExactlyWhenTheClosureThrowsGenerators(): array
    {
        return [
            'throws' => Gen::bool(),
            'payload' => Gen::int(),
        ];
    }
}
