<?php

namespace KirbyEmojis\License;

/**
 * Checks a Pro license key. Swap the implementation with the
 * `jeslage.emojis.license.validator` option (a class name).
 */
interface LicenseValidator
{
    /**
     * Normalizes the entered key, or returns null if it has no valid format
     */
    public function normalize(string $key): string|null;

    /**
     * Activates the key for the given domain. Throws a
     * Kirby\Exception\Exception with a message for the editor if it is invalid.
     */
    public function activate(string $key, string $domain): void;

    /**
     * Checks a stored key (called rarely, the result is not cached by the plugin)
     */
    public function isValid(string $key): bool;
}
