# Расуваефф/результат
[![Latest Stable Version](https://poser.pugx.org/rasuvaeff/result/v)](https://packagist.org/packages/rasuvaeff/result)
[![Total Downloads](https://poser.pugx.org/rasuvaeff/result/downloads)](https://packagist.org/packages/rasuvaeff/result)
[![Build](https://github.com/rasuvaeff/result/actions/workflows/build.yml/badge.svg)](https://github.com/rasuvaeff/result/actions/workflows/build.yml)
[![Static analysis](https://github.com/rasuvaeff/result/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/rasuvaeff/result/actions/workflows/static-analysis.yml)
[![Psalm level](https://img.shields.io/badge/psalm-level_1-blue.svg)](https://github.com/rasuvaeff/result/actions/workflows/static-analysis.yml)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/result/php)](https://packagist.org/packages/rasuvaeff/result)
[![License](https://img.shields.io/badge/license-BSD--3--Clause-blue.svg)](LICENSE.md)
Ввели примитивы `Result<T, E>` и `Option<T>` для PHP 8.3+. Пакет
 не содержит фреймворков и предназначен для обеспечения полезного использования общего вывода Psalm/PHPStan в коде приложения
.

 > Используете помощника по программированию с искусственным интеллектом? [llms.txt](llms.txt) содержит компактную ссылку на API, которой вы можете поделиться с моделью. @@ЛИНИЯ@@
## Требования
- PHP 8.3+
 - Нет зависимостей во время выполнения

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
### Результат
`Result<T, E>` представляет либо успешное значение `Ok<T>`, либо ошибку `Err<E>`.
 `unwrap()` возвращает значение `Ok` и выдаёт `UnwrapException` для `Err`. @@ЛИНИЯ@@
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
 | `Результат::ok($value)` | Создает результат «ОК». |
 | `Результат::ошибка($ошибка)` | Создает результат Err. |
 | `Result::fromThrowable($fn)` | Запускает замыкание и преобразует выброшенный `Throwable` в `Err`. |
 | `isOk()` / `isErr()` | Проверяет активную ветку. |
 | `развернуть()` | Возвращает значение Ok или выдает UnwrapException. |
 | `unwrapOr($default)` | Возвращает значение «ОК» или заданное значение по умолчанию. |
 | `карта($fn)` | Преобразует значение «ОК». |
 | `mapErr($fn)` | Преобразует ошибку Err. |
 | `плоская карта($fn)` | Вызывает замыкание, которое возвращает другой «Результат». |
 | `match(ok: $ok, err: $err)` | Сворачивает обе ветви к одному возвращаемому значению. |
 | `value()` / `error()` | Возвращает полезную нагрузку ветки или значение null. |

 `flatMap()` сохраняет фиксированным канал ошибки: `Result`, возвращаемый замыканием
, должен содержать тот же тип ошибки `E`, что и получатель. Сначала используйте `mapErr()`, если вам
 нужно выровнять типы ошибок перед объединением в цепочку. @@ЛИНИЯ@@
### Вариант
`Option<T>` представляет либо `Some<T>`, либо `None`. `Option::fromNullable()`
 преобразует только `null` в `None`; ложные значения, такие как `0`, `''` и `false`
, остаются `Some`. @@ЛИНИЯ@@
```php
use Rasuvaeff\Result\Option;

$port = Option::fromNullable(value: getenv('PORT') ?: null)
    ->map(fn (string $value): int => (int) $value)
    ->filter(fn (int $value): bool => $value > 0)
    ->toResult(error: 'PORT is missing or invalid');
```
| Метод | Описание |
 |---|---|
 | `Option::some($value)` | Создает параметр «Некоторые». |
 | `Option::none()` | Создает параметр «Нет». |
 | `Option::fromNullable($value)` | Преобразует `null` в `None`, другие значения в `Some`. |
 | `isSome()` / `isNone()` | Проверяет активную ветку. |
 | `развернуть()` | Возвращает значение Some или выдает UnwrapException. |
 | `unwrapOr($default)` | Возвращает значение Some или заданное значение по умолчанию. |
 | `карта($fn)` | Преобразует значение Some. |
 | `фильтр($предикат)` | Сохраняет `Some` только тогда, когда предикат возвращает true. |
 | `toResult($error)` | Преобразует `Some` в `Ok` и `None` в `Err`. | @@ЛИНИЯ@@
## Безопасность
Этот пакет не выполняет ввод-вывод, SQL, команды оболочки, отражение или динамический код
. Он хранит только значения и вызывает замыкания, предоставленные вашим приложением.
 Исключения, вызванные замыканиями, переданными в `map()`, `mapErr()`, `flatMap()`,
 `match()` или `filter()`, не обрабатываются; используйте Result::fromThrowable() на границе
, где желательно преобразование исключения в значение. @@ЛИНИЯ@@
## Примеры
См. [examples/](examples/) для работоспособных сценариев.

 | Скрипт | Шоу | Нужен сервер? |
 |---|---|---|
 | `basic.php` | Использование `Result`, `Option`, `fromThrowable()` и `toResult()` | Нет | @@ЛИНИЯ@@
## Разработка
На хосте нет PHP/Composer. Запускайте команды в Docker через образ `composer:2`:

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer install
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
docker run --rm -v "$PWD":/app -w /app composer:2 composer cs:fix
docker run --rm -v "$PWD":/app -w /app composer:2 composer test
docker run --rm -v "$PWD":/app -w /app composer:2 composer release-check
```
Или с помощью Make:

```bash
make install
make build
make cs-fix
make test
make test-coverage
make mutation
make release-check
```
`make test-coverage` и `makemutation` загружают `pcov` внутри контейнера
 `composer:2`, поскольку базовый образ не имеет драйвера покрытия. @@ЛИНИЯ@@
## Лицензия
[BSD-3-пункт](LICENSE.md)
