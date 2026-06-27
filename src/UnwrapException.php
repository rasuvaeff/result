<?php

declare(strict_types=1);

namespace Rasuvaeff\Result;

/**
 * @api
 */
final class UnwrapException extends \RuntimeException
{
    public static function forResult(): self
    {
        return new self(message: 'Cannot unwrap an Err result');
    }

    public static function forOption(): self
    {
        return new self(message: 'Cannot unwrap a None option');
    }
}
