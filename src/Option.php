<?php

declare(strict_types=1);

namespace Rasuvaeff\Result;

/**
 * @api
 *
 * @template T
 */
final readonly class Option
{
    private function __construct(
        private bool $some,
        private mixed $value,
    ) {}

    /**
     * @template U
     *
     * @param U $value
     *
     * @return self<U>
     */
    public static function some(mixed $value): self
    {
        $option = new self(some: true, value: $value);

        /** @var self<U> $option */
        return $option;
    }

    /**
     * @return self<never>
     */
    public static function none(): self
    {
        $option = new self(some: false, value: null);

        /** @var self<never> $option */
        return $option;
    }

    /**
     * @template U
     *
     * @param U|null $value
     *
     * @return self<U>
     */
    public static function fromNullable(mixed $value): self
    {
        if ($value === null) {
            return self::none();
        }

        return self::some(value: $value);
    }

    public function isSome(): bool
    {
        return $this->some;
    }

    public function isNone(): bool
    {
        return $this->some === false;
    }

    /**
     * @return T
     */
    public function unwrap(): mixed
    {
        if ($this->some === false) {
            throw UnwrapException::forOption();
        }

        return $this->valuePayload();
    }

    /**
     * @param T $default
     *
     * @return T
     */
    public function unwrapOr(mixed $default): mixed
    {
        if ($this->some) {
            return $this->valuePayload();
        }

        return $default;
    }

    /**
     * @template U
     *
     * @param \Closure(T): U $fn
     *
     * @return self<U>
     */
    public function map(\Closure $fn): self
    {
        if ($this->some === false) {
            return self::none();
        }

        /** @var T $value */
        $value = $this->value;

        return self::some(value: $fn($value));
    }

    /**
     * @param \Closure(T): bool $predicate
     *
     * @return self<T>
     */
    public function filter(\Closure $predicate): self
    {
        if ($this->some === false) {
            return self::none();
        }

        /** @var T $value */
        $value = $this->value;
        if (!$predicate($value)) {
            return self::none();
        }

        return $this;
    }

    /**
     * @template E
     *
     * @param E $error
     *
     * @return Result<T, E>
     */
    public function toResult(mixed $error): Result
    {
        if ($this->some) {
            return Result::ok(value: $this->valuePayload());
        }

        return Result::err(error: $error);
    }

    /**
     * @return T
     */
    private function valuePayload(): mixed
    {
        /** @var T $value */
        $value = $this->value;

        return $value;
    }
}
