<?php

declare(strict_types=1);

namespace App\Support\Images;

use RuntimeException;

/** Raised when image bytes cannot be safely re-encoded: unreadable, too large, animated or failing to decode. */
final class ImageReencodingException extends RuntimeException
{
    public static function failed(): self
    {
        return new self('The image could not be re-encoded.');
    }
}
