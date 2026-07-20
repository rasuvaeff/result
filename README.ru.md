# rasuvaeff/result

[![Latest Stable Version](https://poser.pugx.org/rasuvaeff/result/v)](https://packagist.org/packages/rasuvaeff/result)
[![Total Downloads](https://poser.pugx.org/rasuvaeff/result/downloads)](https://packagist.org/packages/rasuvaeff/result)
[![Build](https://github.com/rasuvaeff/result/actions/workflows/build.yml/badge.svg)](https://github.com/rasuvaeff/result/actions/workflows/build.yml)
[![Static analysis](https://github.com/rasuvaeff/result/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/rasuvaeff/result/actions/workflows/static-analysis.yml)
[![Psalm level](https://img.shields.io/badge/psalm-level_1-blue.svg)](https://github.com/rasuvaeff/result/actions/workflows/static-analysis.yml)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/result/php)](https://packagist.org/packages/rasuvaeff/result)
[![License](https://img.shields.io/badge/license-BSD--3--Clause-blue.svg)](LICENSE.md)
[English version](README.md)

Типизированные примитивы `Result<T, E>` и `Option<T>` для PHP 8.3+. Пакет не
зависит от фреймворков и спроектирован так, чтобы generic-вывод Psalm/PHPStan
оставался полезным в прикладном коде.

> Используете AI-ассистента? В [llms.txt](llms.txt) — компактный API-справочник,
> которым можно поделиться с моделью.

## Требования

- PHP 8.3+
- Нет runtime-зависимостей

## Установка

```bash
composer require rasuvaeff/result
```

## Использование

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

`Result<T, E>` представляет либо успешное значение `Ok<T>`, либо ошибку `Err<E>`.
`unwrap()` возвращает значение для `Ok` и выбрасывает `UnwrapException` для `Err`.

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

| Метод | Описание |
|---|---|
| `Result::ok($value)` | Создаёт результат `Ok`. |
| `Result::err($error)` | Создаёт результат `Err`. |
| `Result::fromThrowable($fn)` | Выполняет замыкание и преобразует брошенный `Throwable` в `Err`. |
| `isOk()` / `isErr()` | Проверяет активную ветку. |
| `unwrap()` | Возвращает значение `Ok` либо выбрасывает `UnwrapException`. |
| `unwrapOr($default)` | Возвращает значение `Ok` либо переданное значение по умолчанию. |
| `map($fn)` | Преобразует значение `Ok`. |
| `mapErr($fn)` | Преобразует ошибку `Err`. |
| `flatMap($fn)` | Связывает замыкание, возвращающее другой `Result`. |
| `match(ok: $ok, err: $err)` | Сворачивает обе ветки в одно возвращаемое значение. |
| `value()` / `error()` | Возвращает полезную нагрузку ветки либо `null`. |

`flatMap()` удерживает канал ошибок фиксированным: `Result`, возвращаемый
замыканием, должен нести тот же тип ошибки `E`, что и приёмник. Если перед
связыванием нужно привести типы ошибок, сначала примените `mapErr()`.

### Option

`Option<T>` представляет либо `Some<T>`, либо `None`. `Option::fromNullable()`
преобразует в `None` только `null`; ложные значения вроде `0`, `''` и `false`
остаются `Some`.

```php
use Rasuvaeff\Result\Option;

$port = Option::fromNullable(value: getenv('PORT') ?: null)
    ->map(fn (string $value): int => (int) $value)
    ->filter(fn (int $value): bool => $value > 0)
    ->toResult(error: 'PORT is missing or invalid');
```

| Метод | Описание |
|---|---|
| `Option::some($value)` | Создаёт значение `Some`. |
| `Option::none()` | Создаёт значение `None`. |
| `Option::fromNullable($value)` | Преобразует `null` в `None`, прочие значения — в `Some`. |
| `isSome()` / `isNone()` | Проверяет активную ветку. |
| `unwrap()` | Возвращает значение `Some` либо выбрасывает `UnwrapException`. |
| `unwrapOr($default)` | Возвращает значение `Some` либо переданное значение по умолчанию. |
| `map($fn)` | Преобразует значение `Some`. |
| `filter($predicate)` | Оставляет `Some`, только если предикат возвращает `true`. |
| `toResult($error)` | Преобразует `Some` в `Ok`, а `None` — в `Err`. |

## Безопасность

Пакет не выполняет I/O, SQL, shell-команды, рефлексию и динамический код. Он лишь
хранит значения и вызывает замыкания, предоставленные приложением. Исключения,
выброшенные замыканиями, переданными в `map()`, `mapErr()`, `flatMap()`, `match()`
или `filter()`, не поглощаются; используйте `Result::fromThrowable()` на границе,
где требуется преобразование исключения в значение.

## Примеры

См. [examples/](examples/) — запускаемые скрипты.

| Скрипт | Показывает | Нужен сервер? |
|---|---|---|
| `basic.php` | Использование `Result`, `Option`, `fromThrowable()` и `toResult()` | нет |

## Разработка

На хосте нет PHP/Composer — запускайте команды в Docker через образ `composer:2`:

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer install
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
docker run --rm -v "$PWD":/app -w /app composer:2 composer cs:fix
docker run --rm -v "$PWD":/app -w /app composer:2 composer test
docker run --rm -v "$PWD":/app -w /app composer:2 composer release-check
```

Или через Make:

```bash
make install
make build
make cs-fix
make test
make test-coverage
make mutation
make release-check
```

`make test-coverage` и `make mutation` поднимают `pcov` внутри контейнера
`composer:2`, потому что в базовом образе нет драйвера покрытия.

## Лицензия

[BSD-3-Clause](LICENSE.md)
