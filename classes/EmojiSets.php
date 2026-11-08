<?php

namespace KirbyEmojis;

use Kirby\Content\Field;
use Kirby\Filesystem\Dir;
use Kirby\Filesystem\F;
use Kirby\Toolkit\Str;
use KirbyEmojis\Contracts\SetNaming;
use Stringable;
use Throwable;

/**
 * Image sets (Noto, Fluent, OpenMoji, custom ones). Images are fetched from the set's origin
 * once and stored in the media folder, so visitors never hit the origin.
 */
class EmojiSets
{
    public const NATIVE = 'native';

    public const VARIATION_SELECTOR = 'fe0f';

    // Subdivision flags (🏴 + tag characters) must come before the general case, which would match the 🏴 alone.
    public const EMOJI_PATTERN = '/\p{Regional_Indicator}{2}'
        . '|[#*0-9]\x{FE0F}?\x{20E3}'
        . '|\x{1F3F4}[\x{E0061}-\x{E007A}]+\x{E007F}'
        . '|\p{Extended_Pictographic}(?:\x{FE0F}|\p{Emoji_Modifier})*(?:\x{200D}\p{Extended_Pictographic}(?:\x{FE0F}|\p{Emoji_Modifier})*)*/u';

    public const CODES_PATTERN = '/^[0-9a-f]{1,6}(?:-[0-9a-f]{1,6}){0,15}$/';

    public const BUILT_IN = [
        'noto' => [
            'label'  => 'sets.noto',
            'url'    => 'https://cdn.jsdelivr.net/gh/googlefonts/noto-emoji@e20cbc2bbec1926686be9f9bee7d1d2cfa1fea0e',
            'naming' => 'noto',
        ],
        'fluent' => [
            'label'  => 'sets.fluent',
            'url'    => 'https://cdn.jsdelivr.net/npm/@lobehub/fluent-emoji-flat@1.1.0/assets',
            'naming' => 'fluent',
        ],
        'openmoji' => [
            'label'  => 'sets.openmoji',
            'url'    => 'https://cdn.jsdelivr.net/npm/openmoji@17.0.0/color/svg',
            'naming' => 'openmoji',
        ],
    ];

    public const NAME_PATTERN = '/^[a-z]+$/';

    protected static array $extended = [];

    public static function extend(string $name, array $definition): void
    {
        static::$extended[$name] = [...static::$extended[$name] ?? [], ...$definition];
    }

    public static function flushExtended(): void
    {
        static::$extended = [];
    }

    /**
     * @return array<string, array{label?: string, url: string, naming?: string}>
     */
    public function definitions(): array
    {
        if (Emojis::pro() === false) {
            return [];
        }

        $definitions = self::BUILT_IN;

        foreach ([(array)Emojis::option('sets', []), static::$extended] as $additions) {
            foreach ($additions as $name => $definition) {
                $definitions[$name] = [...$definitions[$name] ?? [], ...(array)$definition];
            }
        }

        $disabled = (array)Emojis::option('disabledSets', []);

        return array_filter(
            $definitions,
            fn ($definition, $name) =>
                in_array('*', $disabled, true) === false &&
                in_array($name, $disabled, true) === false &&
                is_string($name) &&
                preg_match(self::NAME_PATTERN, $name) === 1 &&
                $name !== self::NATIVE &&
                empty($definition['url']) === false,
            ARRAY_FILTER_USE_BOTH
        );
    }

    public function definition(string $set): array|null
    {
        return $this->definitions()[$set] ?? null;
    }

    public function sets(): array
    {
        return [self::NATIVE, ...array_keys($this->definitions())];
    }

    public function isImageSet(string|null $set): bool
    {
        return $set !== null && $set !== self::NATIVE && $this->definition($set) !== null;
    }

    public function label(string $set): string
    {
        if ($set === self::NATIVE) {
            return Emojis::t('sets.native');
        }

        $label = $this->definition($set)['label'] ?? null;

        if ($label === null) {
            return Str::ucfirst($set);
        }

        // Built-in labels are translation keys
        return isset(self::BUILT_IN[$set]) && $label === self::BUILT_IN[$set]['label'] ? Emojis::t($label) : $label;
    }

    /**
     * @return array<string, string> set => label
     */
    public function options(): array
    {
        $options = [];

        foreach ($this->sets() as $set) {
            $options[$set] = $this->label($set);
        }

        return $options;
    }

    public function default(): string
    {
        $set = Emojis::settings()->set();

        return $this->isImageSet($set) ? $set : self::NATIVE;
    }

    public function resolve(string|null $set): string
    {
        if ($set === self::NATIVE || $this->isImageSet($set)) {
            return $set;
        }

        return $this->default();
    }

    public function codepoints(string $emoji): string
    {
        return implode('-', array_map(fn ($char) => dechex(mb_ord($char)), mb_str_split($emoji)));
    }

    public function emojiFromCodes(string $codes): string
    {
        return implode('', array_map(fn ($hex) => mb_chr(hexdec($hex)), explode('-', $codes)));
    }

    /**
     * Sets name their files differently: try fully qualified,
     * then without a trailing FE0F, then without any FE0F.
     */
    public function candidates(string $set, string $codes): array
    {
        $parts    = explode('-', strtolower($codes));
        $variants = [
            $parts,
            end($parts) === self::VARIATION_SELECTOR ? array_slice($parts, 0, -1) : null,
            array_values(array_diff($parts, [self::VARIATION_SELECTOR])),
        ];

        $unique = [];

        foreach (array_filter($variants) as $variant) {
            $unique[implode('-', $variant)] = array_values($variant);
        }

        $naming = $this->definition($set)['naming'] ?? 'fluent';
        $custom = is_string($naming) && class_exists($naming) && is_subclass_of($naming, SetNaming::class) ? new $naming() : null;
        $files  = [];

        if ($custom !== null) {
            foreach ($unique as $p) {
                $files = [...$files, ...$custom->files($p)];
            }

            return array_values(array_unique($files));
        }

        // The built-in sets pad codepoints to at least four digits (1️⃣ = 0031-fe0f-20e3). Sets added with the
        // same naming may not (Twemoji: 31-20e3), so the unpadded names follow as a fallback.
        $padded = array_map(
            fn ($p) => array_map(fn ($code) => str_pad($code, 4, '0', STR_PAD_LEFT), $p),
            array_values($unique)
        );

        foreach ([...$padded, ...array_values($unique)] as $p) {
            $files = [...$files, ...match ($naming) {
                'noto'     => $this->isFlag($p)
                    ? ['third_party/region-flags/waved-svg/emoji_u' . implode('_', $p) . '.svg']
                    : ['2D/svg/emoji_u' . implode('_', $p) . '.svg'],
                'openmoji' => [strtoupper(implode('-', $p)) . '.svg'],
                default    => [implode('-', $p) . '.svg'],
            }];
        }

        return array_values(array_unique($files));
    }

    public function svg(string $set, string $codes): string|null
    {
        if ($this->isImageSet($set) === false) {
            return null;
        }

        $codes = strtolower($codes);
        $path  = $this->path($set, $codes);

        if (F::exists($path) === true) {
            return F::read($path);
        }

        $misses = Emojis::cache('misses');
        $missKey = $set . '/' . $codes;

        if ($misses->exists($missKey) === true) {
            return null;
        }

        $base = rtrim($this->definition($set)['url'], '/');

        foreach ($this->candidates($set, $codes) as $file) {
            $response = Emojis::fetch($base . '/' . $file, 10);

            // Network problem, not a gap in the set: don't remember it as a miss, retry next time
            if ($response === null) {
                return null;
            }

            if ($this->isSvgResponse($response) === true) {
                $this->store($set, $codes, $response['body']);
                return $response['body'];
            }
        }

        $misses->set($missKey, true, 60 * 24);

        return null;
    }

    public function isCached(string $set, string $codes): bool
    {
        return F::exists($this->path($set, strtolower($codes)));
    }

    /**
     * Fetches the given emojis and returns the codes that failed
     */
    public function warm(string $set, array $codesList): array
    {
        $failed = [];

        foreach ($codesList as $codes) {
            $codes = strtolower($codes);

            if ($this->isCached($set, $codes) === false && $this->svg($set, $codes) === null) {
                $failed[] = $codes;
            }
        }

        return $failed;
    }

    public function fallbackSvg(string $codes): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36">'
            . '<text x="50%" y="50%" font-size="30" text-anchor="middle" dominant-baseline="central">'
            . htmlspecialchars($this->emojiFromCodes($codes), ENT_QUOTES) . '</text></svg>';
    }

    public function url(string $set, string $emoji): string
    {
        return $this->baseUrl() . '/' . $set . '/' . $this->codepoints($emoji) . '.svg';
    }

    public function baseUrl(): string
    {
        return rtrim(Emojis::kirby()->url('media'), '/') . '/emojis';
    }

    public function root(): string
    {
        return Emojis::kirby()->root('media') . '/emojis';
    }

    public function path(string $set, string $codes): string
    {
        return $this->root() . '/' . $set . '/' . $codes . '.svg';
    }

    /**
     * Replaces emojis in HTML or text with images: custom `:shortcodes:`
     * and, for an image set, Unicode emojis. Tags and attributes stay untouched.
     */
    public function replace(string|null $html, string $set): string
    {
        $html     = (string)$html;
        $imageSet = $this->isImageSet($set);
        $custom   = Emojis::custom();

        if ($html === '' || ($imageSet === false && str_contains($html, ':') === false)) {
            return $html;
        }

        $result = '';

        foreach (static::splitTags($html) as $part) {
            if ($part === '' || str_starts_with($part, '<')) {
                $result .= $part;
                continue;
            }

            $part = $custom->replace($part);

            if ($imageSet === false) {
                $result .= $part;
                continue;
            }

            // Split again so the <img> tags just inserted for custom emojis stay untouched
            foreach (static::splitTags($part) as $text) {
                $result .= $text === '' || str_starts_with($text, '<')
                    ? $text
                    : preg_replace_callback(self::EMOJI_PATTERN, fn ($m) => $this->img($set, $m[0]), $text);
            }
        }

        return $result;
    }

    public function html(mixed $value, string|null $set = null): string
    {
        if ($value instanceof Field) {
            $value = $value->value();
        }

        if (is_string($value) === false && ($value instanceof Stringable) === false) {
            return '';
        }

        return $this->replace((string)$value, $this->resolve($set));
    }

    public function img(string $set, string $emoji): string
    {
        return sprintf(
            '<img class="emoji" src="%s" alt="%s" width="20" height="20" draggable="false" loading="lazy" decoding="async">',
            htmlspecialchars($this->url($set, $emoji), ENT_QUOTES),
            htmlspecialchars($emoji, ENT_QUOTES),
        );
    }

    /**
     * Codepoints of every emoji and skin tone variant
     */
    public function allCodes(): array
    {
        $codes = [];

        foreach (Emojis::emojibase()->data('en') as $emoji) {
            foreach ([$emoji['emoji'] ?? null, ...array_column($emoji['skins'] ?? [], 'emoji')] as $char) {
                if ($char !== null && $char !== '') {
                    $codes[$this->codepoints($char)] = true;
                }
            }
        }

        return array_keys($codes);
    }

    public function cacheStatus(): array
    {
        try {
            $total = count($this->allCodes());
        } catch (Throwable) {
            $total = null;
        }

        $status = [];

        foreach ($this->sets() as $set) {
            if ($set === self::NATIVE) {
                continue;
            }

            $files = glob($this->root() . '/' . $set . '/*.svg') ?: [];

            $status[] = [
                'set'    => $set,
                'label'  => $this->label($set),
                'cached' => count($files),
                'size'   => array_sum(array_map('filesize', $files)),
                'total'  => $total,
            ];
        }

        return $status;
    }

    public function clear(string $set): int
    {
        $directory = $this->root() . '/' . $set;
        $count     = count(glob($directory . '/*.svg') ?: []);

        Dir::remove($directory);

        // Forget the misses, so emojis missing before are fetched again
        Emojis::cache('misses')->flush();

        return $count;
    }

    /**
     * @return array<int, string>
     */
    public static function splitTags(string $html): array
    {
        return preg_split('/(<[^>]*>)/u', $html, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$html];
    }

    protected function store(string $set, string $codes, string $svg): void
    {
        F::write($this->path($set, $codes), $svg);
    }

    protected function isSvgResponse(array $response): bool
    {
        return $response['code'] >= 200 && $response['code'] < 300 && str_contains($response['body'], '<svg');
    }

    /**
     * Country flags (two regional indicators) and subdivision flags (🏴 + tag characters)
     */
    protected function isFlag(array $parts): bool
    {
        if (($parts[0] ?? null) === '1f3f4' && count($parts) > 2 && end($parts) === 'e007f') {
            return true;
        }

        if (count($parts) !== 2) {
            return false;
        }

        foreach ($parts as $part) {
            $code = hexdec($part);

            if ($code < 0x1F1E6 || $code > 0x1F1FF) {
                return false;
            }
        }

        return true;
    }
}
