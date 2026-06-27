<?php

declare(strict_types=1);

namespace Rasuvaeff\Result\Benchmarks;

use Rasuvaeff\Result\Result;
use Testo\Bench;

final class ResultBench
{
    #[Bench(
        callables: [
            'plain' => [self::class, 'plainValue'],
        ],
        calls: 100_000,
        iterations: 10,
    )]
    public static function okAndMap(): Result
    {
        return Result::ok(41)
            ->map(static fn(int $value): int => $value + 1);
    }

    public static function plainValue(): int
    {
        return 42;
    }
}
