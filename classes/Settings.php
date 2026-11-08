<?php

namespace KirbyEmojis;

use Kirby\Data\Data;
use Kirby\Filesystem\F;

/**
 * Site-wide settings from the Panel's Emojis view, stored in a YAML file.
 * Unsaved values fall back to the plugin options.
 * Precedence: field blueprint > these settings > options.
 */
class Settings
{
    public const KEYS = [
        'emoji_set',
        'categories',
        'skin_tone',
        'skin_tone_selector',
        'suggestions',
        'recent_emojis',
        'custom_emojis',
    ];

    protected array|null $values = null;

    public function file(): string
    {
        return Emojis::option('settings', Emojis::kirby()->root('config') . '/emojis.yml');
    }

    public function all(): array
    {
        if ($this->values !== null) {
            return $this->values;
        }

        $file = $this->file();

        return $this->values = F::exists($file) ? (array)Data::read($file) : [];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->all()[$key] ?? null;

        return $value === null || $value === '' || $value === [] ? $default : $value;
    }

    /**
     * Saves the given values. Keys that are not passed keep their value,
     * so the free edition doesn't wipe Pro settings.
     */
    public function save(array $values): void
    {
        $values = array_intersect_key($values, array_flip(self::KEYS));
        $data   = [...$this->all(), ...$values];

        $data['emoji_set']          = $this->validSet($data['emoji_set'] ?? null);
        $data['categories']         = array_values(array_intersect(array_keys(Emojibase::CATEGORIES), (array)($data['categories'] ?? [])));
        $data['skin_tone']          = in_array($data['skin_tone'] ?? null, Emojibase::SKIN_TONES, true) ? $data['skin_tone'] : null;

        foreach (['skin_tone_selector', 'suggestions', 'recent_emojis', 'custom_emojis'] as $key) {
            $data[$key] = array_key_exists($key, $data) && $data[$key] !== null && $data[$key] !== ''
                ? static::bool($data[$key], true)
                : null;
        }

        $data = array_filter($data, fn ($value) => $value !== null && $value !== '');

        Data::write($this->file(), $data);

        $this->values = null;
        Emojis::flush();
    }

    public function set(): string
    {
        return (string)$this->get('emoji_set', Emojis::option('set', EmojiSets::NATIVE));
    }

    public function customEmojis(): bool
    {
        return Emojis::pro() && static::bool($this->get('custom_emojis'), (bool)Emojis::option('custom.enabled', true));
    }

    public function recentEmojis(): bool
    {
        return static::bool($this->get('recent_emojis'), true);
    }

    public function suggestions(): bool
    {
        return static::bool($this->get('suggestions'), true);
    }

    /**
     * All selected = empty, so the picker has no category limit
     */
    public function categories(): array
    {
        $all        = array_keys(Emojibase::CATEGORIES);
        $categories = array_values(array_intersect($all, (array)$this->get('categories', [])));

        return count($categories) === count($all) ? [] : $categories;
    }

    public function skinTone(): string
    {
        $tone = $this->get('skin_tone', 'none');

        return in_array($tone, Emojibase::SKIN_TONES, true) ? $tone : 'none';
    }

    public function skinToneSelector(): bool
    {
        return static::bool($this->get('skin_tone_selector'), true);
    }

    /**
     * Site-wide settings for the Panel
     */
    public function toScript(): array
    {
        $sets = Emojis::sets();

        return [
            'set'              => $sets->default(),
            'imageUrl'         => $sets->baseUrl(),
            'custom'           => Emojis::custom()->isEnabled(),
            'categories'       => $this->categories(),
            'skinTone'         => $this->skinTone(),
            'skinToneSelector' => $this->skinToneSelector(),
            'recent'           => $this->recentEmojis(),
            'suggestions'      => $this->suggestions(),
        ];
    }

    /**
     * Values for the settings form (all categories when unlimited)
     */
    public function toForm(): array
    {
        return [
            'emoji_set'          => Emojis::sets()->default(),
            'categories'         => $this->categories() ?: array_keys(Emojibase::CATEGORIES),
            'skin_tone'          => $this->skinTone(),
            'skin_tone_selector' => $this->skinToneSelector(),
            'suggestions'        => $this->suggestions(),
            'recent_emojis'      => $this->recentEmojis(),
            'custom_emojis'      => static::bool($this->get('custom_emojis'), (bool)Emojis::option('custom.enabled', true)),
        ];
    }

    public static function bool(mixed $value, bool $default): bool
    {
        return match (true) {
            $value === true, $value === 'show', $value === 'true', $value === 1, $value === '1'   => true,
            $value === false, $value === 'hide', $value === 'false', $value === 0, $value === '0' => false,
            default => $default,
        };
    }

    protected function validSet(mixed $set): string|null
    {
        if (is_string($set) === false || $set === '') {
            return null;
        }

        // Unknown sets are kept in the free edition, where no image set is available
        return in_array($set, Emojis::sets()->sets(), true) || Emojis::pro() === false ? $set : null;
    }
}
