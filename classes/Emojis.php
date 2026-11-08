<?php

namespace KirbyEmojis;

use Closure;
use Kirby\Cache\Cache;
use Kirby\Cms\App;
use Kirby\Http\Remote;
use Throwable;

/**
 * Entry point to the plugin's services, one set per Kirby instance
 */
class Emojis
{
    public const PLUGIN = 'jeslage/emojis';

    public const OPTIONS = 'jeslage.emojis';

    public const PRO_URL = 'https://github.com/jeslage/kirby-emojis#editions';

    /**
     * Fetches a URL: fn (string $url, int $timeout): ?array{code: int, body: string}.
     * Returns null on connection errors. Replaceable in tests.
     */
    public static Closure|null $http = null;

    protected static App|null $kirby = null;

    protected static array $instances = [];

    public static function kirby(): App
    {
        return App::instance();
    }

    public static function option(string $key, mixed $default = null): mixed
    {
        $value = static::kirby()->option(self::OPTIONS . '.' . $key, $default);

        return $value instanceof Closure ? $value(static::kirby()) : $value;
    }

    public static function cache(string $name): Cache
    {
        return static::kirby()->cache(self::OPTIONS . '.' . $name);
    }

    public static function sets(): EmojiSets
    {
        return static::service(EmojiSets::class);
    }

    public static function emojibase(): Emojibase
    {
        return static::service(Emojibase::class);
    }

    public static function custom(): CustomEmojis
    {
        return static::service(CustomEmojis::class);
    }

    public static function settings(): Settings
    {
        return static::service(Settings::class);
    }

    public static function license(): License
    {
        return static::service(License::class);
    }

    public static function pro(): bool
    {
        return static::license()->pro();
    }

    /**
     * @param array<string, mixed> $replace
     */
    public static function t(string $key, array $replace = []): string
    {
        $text = t(self::OPTIONS . '.' . $key) ?? $key;

        foreach ($replace as $name => $value) {
            $text = str_replace('{' . $name . '}', (string)$value, $text);
        }

        return $text;
    }

    /**
     * @return array{code: int, body: string}|null
     */
    public static function fetch(string $url, int $timeout = 10): array|null
    {
        if (static::$http !== null) {
            return (static::$http)($url, $timeout);
        }

        try {
            $response = Remote::get($url, ['timeout' => $timeout]);
        } catch (Throwable) {
            return null;
        }

        return ['code' => (int)$response->code(), 'body' => (string)$response->content()];
    }

    /**
     * Drops all service instances, e.g. after the settings changed
     */
    public static function flush(): void
    {
        static::$instances = [];
    }

    /**
     * @template T
     * @param class-string<T> $class
     * @return T
     */
    protected static function service(string $class): object
    {
        // A new Kirby instance (tests, CLI) gets fresh services
        if (static::$kirby !== static::kirby()) {
            static::$kirby     = static::kirby();
            static::$instances = [];
        }

        return static::$instances[$class] ??= new $class();
    }
}
