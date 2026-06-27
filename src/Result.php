<?php

declare(strict_types=1);

namespace Rasuvaeff\Result;

/**
 * @api
 *
 * @template T
 * @template E
 */
final readonly class Result
{
    private function __construct(
        private bool $ok,
        private mixed $value,
        private mixed $error,
    ) {}

    /**
     * @template U
     *
     * @param U $value
     *
     * @return self<U, never>
     */
    public static function ok(mixed $value): self
    {
        $result = new self(ok: true, value: $value, error: null);

        /** @var self<U, never> $result */
        return $result;
    }

    /**
     * @template F
     *
     * @param F $error
     *
     * @return self<never, F>
     */
    public static function err(mixed $error): self
    {
        $result = new self(ok: false, value: null, error: $error);

        /** @var self<never, F> $result */
        return $result;
    }

    /**
     * @template U
     *
     * @param \Closure(): U $fn
     *
     * @return self<U, \Throwable>
     */
    public static function fromThrowable(\Closure $fn): self
    {
        try {
            return self::ok(value: $fn());
        } catch (\Throwable $exception) {
            return self::err(error: $exception);
        }
    }

    public function isOk(): bool
    {
        return $this->ok;
    }

    public function isErr(): bool
    {
        return $this->ok === false;
    }

    /**
     * @return T
     */
    public function unwrap(): mixed
    {
        if ($this->ok === false) {
            throw UnwrapException::forResult();
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
        if ($this->ok) {
            return $this->valuePayload();
        }

        return $default;
    }

    /**
     * @template U
     *
     * @param \Closure(T): U $fn
     *
     * @return self<U, E>
     */
    public function map(\Closure $fn): self
    {
        if ($this->ok === false) {
            /** @var self<U, E> $result */
            $result = $this;

            return $result;
        }

        /** @var T $value */
        $value = $this->value;
        $result = new self(ok: true, value: $fn($value), error: null);

        /** @var self<U, E> $result */
        return $result;
    }

    /**
     * @template F
     *
     * @param \Closure(E): F $fn
     *
     * @return self<T, F>
     */
    public function mapErr(\Closure $fn): self
    {
        if ($this->ok) {
            /** @var self<T, F> $result */
            $result = $this;

            return $result;
        }

        /** @var E $error */
        $error = $this->error;
        $result = new self(ok: false, value: null, error: $fn($error));

        /** @var self<T, F> $result */
        return $result;
    }

    /**
     * @template U
     *
     * @param \Closure(T): self<U, E> $fn
     *
     * @return self<U, E>
     */
    public function flatMap(\Closure $fn): self
    {
        if ($this->ok === false) {
            /** @var self<U, E> $result */
            $result = $this;

            return $result;
        }

        /** @var T $value */
        $value = $this->value;

        return $fn($value);
    }

    /**
     * @template U
     *
     * @param \Closure(T): U $ok
     * @param \Closure(E): U $err
     *
     * @return U
     */
    public function match(\Closure $ok, \Closure $err): mixed
    {
        if ($this->ok) {
            /** @var T $value */
            $value = $this->value;

            return $ok($value);
        }

        /** @var E $error */
        $error = $this->error;

        return $err($error);
    }

    /**
     * @return T|null
     */
    public function value(): mixed
    {
        return $this->ok ? $this->valuePayload() : null;
    }

    /**
     * @return E|null
     */
    public function error(): mixed
    {
        return $this->ok ? null : $this->errorPayload();
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

    /**
     * @return E
     */
    private function errorPayload(): mixed
    {
        /** @var E $error */
        $error = $this->error;

        return $error;
    }
}
