<?php

namespace KirbyEmojis;

use Kirby\Cms\File;
use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Toolkit\Str;

/**
 * Custom emojis (Pro): image files with the `emoji` file template on the
 * configured parent (the site by default), used as `:shortcode:`
 */
class CustomEmojis
{
    public const SHORTCODE_PATTERN = '/:([a-z0-9_+-]+):/';

    protected const CACHE_KEY = 'list';

    protected array|null $list = null;

    public function isEnabled(): bool
    {
        return Emojis::settings()->customEmojis() && $this->parent() !== null;
    }

    public function template(): string
    {
        return Emojis::option('custom.template', 'emoji');
    }

    public function parent(): Site|Page|null
    {
        $parent = Emojis::option('custom.parent', 'site');

        if ($parent instanceof Site || $parent instanceof Page) {
            return $parent;
        }

        if ($parent === 'site' || $parent === null || $parent === '') {
            return Emojis::kirby()->site();
        }

        return Emojis::kirby()->page($parent);
    }

    /**
     * @return array<string, array{shortcode: string, label: string, keywords: array, url: string, id: string}>
     */
    public function all(): array
    {
        if ($this->isEnabled() === false) {
            return [];
        }

        // Keyed by the files' names and modification times, so files added or
        // changed outside the Panel (deployments, Git) are picked up as well
        return $this->list ??= Emojis::cache('custom')->getOrSet(
            self::CACHE_KEY . '-' . $this->signature(),
            fn () => $this->load()
        );
    }

    public function find(string|null $shortcode): array|null
    {
        return $this->all()[trim((string)$shortcode, ':')] ?? null;
    }

    public function clearCache(): void
    {
        $this->list = null;
        Emojis::cache('custom')->flush();
    }

    protected function signature(): string
    {
        $parts = [];

        foreach ($this->files() as $file) {
            $parts[] = $file->filename() . ':' . $file->modified();
        }

        return md5(implode('|', $parts));
    }

    protected function files(): \Kirby\Cms\Files
    {
        return $this->parent()
            ->files()
            ->filter(fn (File $file) => $file->template() === $this->template())
            ->filter(fn (File $file) => $file->type() === 'image')
            ->sortBy('filename', 'asc');
    }

    public function owns(File $file): bool
    {
        $parent = $this->parent();

        return
            $parent !== null &&
            $file->parent()->id() === $parent->id() &&
            $file->parent()::class === $parent::class &&
            $file->template() === $this->template();
    }

    public static function shortcodeFor(File $file): string
    {
        $shortcode = $file->content()->get('shortcode')->value();

        return static::normalize($shortcode ?: $file->name());
    }

    public static function normalize(string $value): string
    {
        $value = Str::lower(Str::ascii(trim($value, ': ')));

        return trim(preg_replace('/[^a-z0-9_+-]+/', '-', $value), '-');
    }

    public function replace(string $text): string
    {
        if (str_contains($text, ':') === false || $this->isEnabled() === false) {
            return $text;
        }

        return preg_replace_callback(self::SHORTCODE_PATTERN, function ($match) {
            $emoji = $this->find($match[1]);
            return $emoji ? $this->img($emoji) : $match[0];
        }, $text);
    }

    public function img(array $emoji): string
    {
        return sprintf(
            '<img class="emoji emoji-custom" src="%s" alt="%s" title=":%s:" width="20" height="20" draggable="false" loading="lazy" decoding="async">',
            htmlspecialchars($emoji['url'], ENT_QUOTES),
            htmlspecialchars($emoji['label'], ENT_QUOTES),
            htmlspecialchars($emoji['shortcode'], ENT_QUOTES),
        );
    }

    /**
     * The list for the Panel
     */
    public function toArray(): array
    {
        return array_values($this->all());
    }

    public function load(): array
    {
        $list = [];

        foreach ($this->files() as $file) {
            $shortcode = static::shortcodeFor($file);

            if ($shortcode === '' || isset($list[$shortcode]) === true) {
                continue;
            }

            $alt = $file->content()->get('alt')->value();

            $list[$shortcode] = [
                'shortcode' => $shortcode,
                'label'     => $alt ?: Str::ucfirst(str_replace(['-', '_'], ' ', $shortcode)),
                'keywords'  => array_values(array_filter(Str::split($file->content()->get('keywords')->value() ?? '', ','))),
                'url'       => $file->url(),
                'id'        => $file->id(),
            ];
        }

        return $list;
    }
}
