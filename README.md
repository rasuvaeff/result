# rasuvaeff/result

[![Latest Stable Version](https://poser.pugx.org/rasuvaeff/result/v)](https://packagist.org/packages/rasuvaeff/result)
[![Total Downloads](https://poser.pugx.org/rasuvaeff/result/downloads)](https://packagist.org/packages/rasuvaeff/result)
[![Build](https://github.com/rasuvaeff/result/actions/workflows/build.yml/badge.svg)](https://github.com/rasuvaeff/result/actions/workflows/build.yml)
[![Static analysis](https://github.com/rasuvaeff/result/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/rasuvaeff/result/actions/workflows/static-analysis.yml)
[![Psalm level](https://img.shields.io/badge/psalm-level_1-blue.svg)](https://github.com/rasuvaeff/result/actions/workflows/static-analysis.yml)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/result/php)](https://packagist.org/packages/rasuvaeff/result)
[![License](https://img.shields.io/badge/license-BSD--3--Clause-blue.svg)](LICENSE.md)
[Русская версия](README.ru.md)

Typed `Result<T, E>` and `Option<T>` primitives for PHP 8.3+. The package is
framework-free and designed to keep Psalm/PHPStan generic inference useful in
application code.

> Using an AI coding assistant? [llms.txt](llms.txt) contains a compact API reference you can share with the model.

## Requirements

- PHP 8.3+
- No runtime dependencies

## Installation

```bash
composer require rasuvaeff/result
```

## Usage

```php
use Rasuvaeff\Result\Option;
use Rasuvaeff\Result\Result;

$user = Result::fromThrowable(
    fn (): array => ['id' => 42, 'email' => 'user@example.com'],
)
    ->map(fn (array $row): string => $row['email'])
    ->unwrapOr(default: 'anonymous@example.com');

$name = Option::fromNullable(value: getenv('USER_NAME') ?: null)
    ->filter(fn (string $value): bool => $value !== '')
    ->unwrapOr(default: 'guest');
```

### Result

`Result<T, E>` represents either a successful `Ok<T>` value or an `Err<E>` error.
`unwrap()` returns the value for `Ok` and throws `UnwrapException` for `Err`.

```php
use Rasuvaeff\Result\Result;

$result = Result::ok(value: 21)
    ->map(fn (int $value): int => $value * 2)
    ->flatMap(fn (int $value): Result => $value > 40
        ? Result::ok(value: $value)
        : Result::err(error: 'too small'));

$message = $result->match(
    ok: fn (int $value): string => "Value: {$value}",
    err: fn (string $error): string => "Error: {$error}",
);
```

| Method | Description |
|---|---|
| `Result::ok($value)` | Creates an `Ok` result. |
| `Result::err($error)` | Creates an `Err` result. |
| `Result::fromThrowable($fn)` | Runs a closure and converts thrown `Throwable` to `Err`. |
| `isOk()` / `isErr()` | Checks the active branch. |
| `unwrap()` | Returns the `Ok` value or throws `UnwrapException`. |
| `unwrapOr($default)` | Returns the `Ok` value or the supplied default. |
| `map($fn)` | Transforms the `Ok` value. |
| `mapErr($fn)` | Transforms the `Err` error. |
| `flatMap($fn)` | Chains a closure that returns another `Result`. |
| `match(ok: $ok, err: $err)` | Folds both branches to one return value. |
| `value()` / `error()` | Returns the branch payload or `null`. |

`flatMap()` keeps the error channel fixed: the `Result` returned by the closure
must carry the same `E` error type as the receiver. Use `mapErr()` first if you
need to align error types before chaining.

### Option

`Option<T>` represents either `Some<T>` or `None`. `Option::fromNullable()`
converts only `null` to `None`; falsey values such as `0`, `''`, and `false`
remain `Some`.

```php
use Rasuvaeff\Result\Option;

$port = Option::fromNullable(value: getenv('PORT') ?: null)
    ->map(fn (string $value): int => (int) $value)
    ->filter(fn (int $value): bool => $value > 0)
    ->toResult(error: 'PORT is missing or invalid');
```

| Method | Description |
|---|---|
| `Option::some($value)` | Creates a `Some` option. |
| `Option::none()` | Creates a `None` option. |
| `Option::fromNullable($value)` | Converts `null` to `None`, other values to `Some`. |
| `isSome()` / `isNone()` | Checks the active branch. |
| `unwrap()` | Returns the `Some` value or throws `UnwrapException`. |
| `unwrapOr($default)` | Returns the `Some` value or the supplied default. |
| `map($fn)` | Transforms the `Some` value. |
| `filter($predicate)` | Keeps `Some` only when the predicate returns true. |
| `toResult($error)` | Converts `Some` to `Ok` and `None` to `Err`. |

## Security

This package does not execute I/O, SQL, shell commands, reflection, or dynamic
code. It only stores values and calls closures supplied by your application.
Exceptions thrown by closures passed to `map()`, `mapErr()`, `flatMap()`,
`match()`, or `filter()` are not swallowed; use `Result::fromThrowable()` at the
boundary where exception-to-value conversion is desired.

## Examples

See [examples/](examples/) for runnable scripts.

| Script | Shows | Needs server? |
|---|---|---|
| `basic.php` | `Result`, `Option`, `fromThrowable()`, and `toResult()` usage | No |

## Development

No PHP/Composer on the host. Run commands in Docker via the `composer:2` image:

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer install
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
docker run --rm -v "$PWD":/app -w /app composer:2 composer cs:fix
docker run --rm -v "$PWD":/app -w /app composer:2 composer test
docker run --rm -v "$PWD":/app -w /app composer:2 composer release-check
```

Or with Make:

```bash
make install
make build
make cs-fix
make test
make test-coverage
make mutation
make release-check
```

`make test-coverage` and `make mutation` bootstrap `pcov` inside the
`composer:2` container because the base image has no coverage driver.

## License

[BSD-3-Clause](LICENSE.md)
