<?php

namespace KirbyEmojis\License;

use Kirby\Exception\InvalidArgumentException;

/**
 * Placeholder validator that only checks the key format (`KEMO-XXXX-XXXX-XXXX-XXXX`).
 * Replace it with a validator that asks the license store before selling Pro.
 */
class KeyFormatValidator implements LicenseValidator
{
    public const PATTERN = '/^KEMO(?:-[A-Z0-9]{4}){4}$/';

    public function normalize(string $key): string|null
    {
        $key = strtoupper(trim($key));

        return preg_match(self::PATTERN, $key) === 1 ? $key : null;
    }

    public function activate(string $key, string $domain): void
    {
        if ($this->normalize($key) === null) {
            throw new InvalidArgumentException(message: \KirbyEmojis\Emojis::t('license.invalid'));
        }
    }

    public function isValid(string $key): bool
    {
        return $this->normalize($key) !== null;
    }
}
