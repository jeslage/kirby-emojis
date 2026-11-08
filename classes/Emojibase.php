<?php

namespace KirbyEmojis;

use Kirby\Exception\Exception;

/**
 * Emoji data from Emojibase, fetched on the server and cached,
 * so the Panel never contacts the CDN
 */
class Emojibase
{
    // Keep in sync with src/shared/locale.js
    public const LOCALES = [
        'bn', 'da', 'de', 'en-gb', 'en', 'es-mx', 'es', 'et', 'fi', 'fr', 'hi', 'hu', 'it', 'ja',
        'ko', 'lt', 'ms', 'nb', 'nl', 'pl', 'pt', 'ru', 'sv', 'th', 'uk', 'vi', 'zh-hant', 'zh',
    ];

    public const SKIN_TONES = ['none', 'light', 'medium-light', 'medium', 'medium-dark', 'dark'];

    // Emojibase group indexes; group 2 (skin tone and hair components) is never shown
    public const CATEGORIES = [
        'smileys-people' => [0, 1],
        'animals-nature' => [3],
        'food-drink'     => [4],
        'travel-places'  => [5],
        'activities'     => [6],
        'objects'        => [7],
        'symbols'        => [8],
        'flags'          => [9],
    ];

    public const SHORTCODES = 'shortcodes/emojibase.json';

    protected array $labels = [];

    /**
     * Same as emojibaseLocale() in src/shared/locale.js
     */
    public static function locale(string|null $locale): string
    {
        $normalized = str_replace('_', '-', strtolower((string)($locale ?: 'en')));

        if (in_array($normalized, ['zh-tw', 'zh-hk', 'zh-mo', 'zh-hant'], true)) {
            return 'zh-hant';
        }

        if (static::isLocale($normalized) === true) {
            return $normalized;
        }

        $language = explode('-', $normalized)[0];

        if (in_array($language, ['no', 'nn'], true)) {
            return 'nb';
        }

        return static::isLocale($language) ? $language : 'en';
    }

    public static function isLocale(string $locale): bool
    {
        return in_array($locale, self::LOCALES, true);
    }

    /**
     * Categories as an array of keys from a comma separated string ("all" = none)
     */
    public static function parseCategories(string $value): array
    {
        if ($value === 'all') {
            return [];
        }

        return array_values(array_intersect(array_keys(self::CATEGORIES), explode(',', $value)));
    }

    /**
     * @return array<string, string> emoji => label
     */
    public function labels(string $locale): array
    {
        $locale = static::locale($locale);

        if (isset($this->labels[$locale]) === true) {
            return $this->labels[$locale];
        }

        $labels = [];

        foreach ($this->data($locale) as $emoji) {
            if (empty($emoji['emoji']) === false && empty($emoji['label']) === false) {
                $labels[$emoji['emoji']] = $emoji['label'];
            }

            foreach ($emoji['skins'] ?? [] as $skin) {
                if (empty($skin['emoji']) === false && empty($skin['label']) === false) {
                    $labels[$skin['emoji']] = $skin['label'];
                }
            }
        }

        return $this->labels[$locale] = $labels;
    }

    public function data(string $locale, array $categories = []): array
    {
        $emojis = $this->fetch($locale, 'data.json');
        $groups = [];

        foreach ($categories as $key) {
            $groups = [...$groups, ...self::CATEGORIES[$key] ?? []];
        }

        if ($groups === []) {
            return $emojis;
        }

        return array_values(array_filter(
            $emojis,
            fn ($emoji) => isset($emoji['group']) && in_array($emoji['group'], $groups, true)
        ));
    }

    public function messages(string $locale): array
    {
        return $this->fetch($locale, 'messages.json');
    }

    /**
     * English for every locale, as shortcodes are typed rather than read
     */
    public function shortcodes(): array
    {
        return $this->fetch('en', self::SHORTCODES);
    }

    public function clear(): void
    {
        Emojis::cache('emojibase')->flush();
        $this->labels = [];
    }

    protected function url(string $locale, string $file): string
    {
        return rtrim(Emojis::option('emojibase.url'), '/') . '/' . $locale . '/' . $file;
    }

    protected function fetch(string $locale, string $file): array
    {
        $url   = $this->url($locale, $file);
        $cache = Emojis::cache('emojibase');
        $key   = md5($url);

        $data = $cache->get($key);

        if (is_array($data) === true) {
            return $data;
        }

        $response = Emojis::fetch($url, 15);

        if ($response === null || $response['code'] < 200 || $response['code'] >= 300) {
            throw new Exception(message: 'Could not load the emoji data from ' . $url);
        }

        $data = json_decode($response['body'], true);

        if (is_array($data) === false) {
            throw new Exception(message: 'Invalid emoji data from ' . $url);
        }

        $cache->set($key, $data, (int)Emojis::option('emojibase.ttl', 60 * 24 * 7));

        return $data;
    }
}
