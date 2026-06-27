<?php

declare(strict_types=1);

use Rasuvaeff\Result\Option;
use Rasuvaeff\Result\Result;

require dirname(__DIR__) . '/vendor/autoload.php';

$parsed = Result::fromThrowable(
    fn(): array => json_decode(json: '{"enabled":true}', associative: true, flags: JSON_THROW_ON_ERROR),
);

$enabled = $parsed
    ->map(fn(array $payload): bool => $payload['enabled'] ?? false)
    ->unwrapOr(default: false);

$name = Option::fromNullable(value: getenv('RESULT_EXAMPLE_NAME') ?: null)
    ->filter(fn(string $value): bool => $value !== '')
    ->toResult(error: 'RESULT_EXAMPLE_NAME is not set')
    ->unwrapOr(default: 'guest');

echo sprintf("enabled=%s name=%s\n", $enabled ? 'yes' : 'no', $name);
