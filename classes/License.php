<?php

namespace KirbyEmojis;

use Kirby\Data\Data;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Filesystem\F;
use Kirby\Plugin\License as PluginLicense;
use Kirby\Plugin\LicenseStatus;
use Kirby\Plugin\Plugin;
use KirbyEmojis\License\KeyFormatValidator;
use KirbyEmojis\License\LicenseValidator;

/**
 * Free and Pro editions. Pro needs an activated license key in production;
 * on local environments Pro features are available for development.
 */
class License
{
    public const DIALOG = 'emojis/license';

    protected array|null $data = null;

    public function file(): string
    {
        return Emojis::option('license.file', Emojis::kirby()->root('config') . '/.emojis-license');
    }

    public function validator(): LicenseValidator
    {
        $class = Emojis::option('license.validator', KeyFormatValidator::class);

        return new $class();
    }

    /**
     * Forced edition from the `edition` option. Only "free" has an effect,
     * e.g. to try the free edition locally.
     */
    public function forcedFree(): bool
    {
        return Emojis::option('edition') === 'free';
    }

    public function key(): string|null
    {
        $this->data ??= F::exists($this->file()) ? (array)Data::read($this->file(), 'json') : [];

        return $this->data['key'] ?? null;
    }

    public function isActivated(): bool
    {
        $key = $this->key();

        return $key !== null && $this->validator()->isValid($key);
    }

    public function isLocal(): bool
    {
        $kirby = Emojis::kirby();

        if ($kirby->system()->isLocal() === true) {
            return true;
        }

        // The command line has no request host: use the site URL's host. Without a
        // configured URL it's unknown; Pro on the CLI only affects the cache commands.
        if ($kirby->environment()->cli() === true) {
            $host = parse_url($kirby->url('index'), PHP_URL_HOST) ?: '';

            return
                $host === '' ||
                $host === 'localhost' ||
                str_ends_with($host, '.local') ||
                str_ends_with($host, '.test') ||
                str_ends_with($host, '.ddev.site');
        }

        return false;
    }

    public function pro(): bool
    {
        if ($this->forcedFree() === true) {
            return false;
        }

        return $this->isActivated() || $this->isLocal();
    }

    public function activate(string $key, string|null $email = null): void
    {
        $validator  = $this->validator();
        $normalized = $validator->normalize($key);

        if ($normalized === null) {
            throw new InvalidArgumentException(message: Emojis::t('license.invalid'));
        }

        $validator->activate($normalized, Emojis::kirby()->environment()->host() ?? '');

        Data::write($this->file(), [
            'key'       => $normalized,
            'email'     => $email,
            'domain'    => Emojis::kirby()->environment()->host(),
            'activated' => date('c'),
        ], 'json');

        $this->data = null;
        Emojis::flush();
    }

    public function edition(): string
    {
        return $this->pro() ? 'pro' : 'free';
    }

    /**
     * License info for the Panel's system view
     */
    public function toPluginLicense(Plugin $plugin): PluginLicense
    {
        $status = match (true) {
            $this->forcedFree()  => new LicenseStatus(
                value: 'free',
                icon: 'heart',
                label: Emojis::t('license.free'),
                theme: 'passive',
            ),
            $this->isActivated() => new LicenseStatus(
                value: 'active',
                icon: 'check',
                label: Emojis::t('license.active'),
                theme: 'positive',
            ),
            $this->isLocal()     => new LicenseStatus(
                value: 'demo',
                icon: 'preview',
                label: Emojis::t('license.local'),
                dialog: self::DIALOG,
                theme: 'notice',
            ),
            default              => new LicenseStatus(
                value: 'missing',
                icon: 'key',
                label: Emojis::t('license.missing'),
                dialog: self::DIALOG,
                theme: 'love',
            ),
        };

        return new PluginLicense(
            plugin: $plugin,
            name: 'Kirby Emojis ' . ($this->isActivated() ? 'Pro' : 'Free'),
            link: Emojis::PRO_URL,
            status: $status,
        );
    }
}
