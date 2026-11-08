<?php

namespace KirbyEmojis\Values;

use JsonSerializable;
use KirbyEmojis\CustomEmojis;
use KirbyEmojis\Emojis;
use Stringable;
use Throwable;

/**
 * A single emoji (Unicode or custom `:shortcode:`) with its label and image URL
 */
class EmojiValue implements JsonSerializable, Stringable
{
    protected array|null $resolved = null;

    public function __construct(
        protected string $value,
        protected string $set,
        protected string|null $locale = null,
    ) {
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function set(): string
    {
        return $this->set;
    }

    public function isEmpty(): bool
    {
        return $this->value === '';
    }

    public function isCustom(): bool
    {
        return preg_match('/^' . trim(CustomEmojis::SHORTCODE_PATTERN, '/') . '$/', $this->value) === 1;
    }

    public function label(): string|null
    {
        return $this->toArray()['label'];
    }

    public function url(): string|null
    {
        return $this->toArray()['url'];
    }

    public function shortcode(): string|null
    {
        return $this->toArray()['shortcode'];
    }

    /**
     * The emoji as HTML: an image for custom emojis and image sets, the character otherwise
     */
    public function html(): string
    {
        if ($this->isEmpty()) {
            return '';
        }

        return Emojis::sets()->replace(htmlspecialchars($this->value, ENT_QUOTES), $this->set);
    }

    public function toArray(): array
    {
        return $this->resolved ??= $this->resolve();
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    protected function resolve(): array
    {
        $emoji = $this->value;
        $set   = $this->set;

        if ($this->isCustom()) {
            $custom = Emojis::custom()->find($emoji);

            return [
                'value'     => $emoji,
                'label'     => $custom['label'] ?? null,
                'url'       => $custom['url'] ?? null,
                'set'       => $set,
                'custom'    => true,
                'shortcode' => trim($emoji, ':'),
            ];
        }

        $sets = Emojis::sets();

        return [
            'value'     => $emoji,
            'label'     => $emoji === '' ? null : $this->labelFor($emoji),
            'url'       => $emoji !== '' && $sets->isImageSet($set) ? $sets->url($set, $emoji) : null,
            'set'       => $set,
            'custom'    => false,
            'shortcode' => null,
        ];
    }

    protected function labelFor(string $emoji): string|null
    {
        try {
            $labels = Emojis::emojibase()->labels($this->locale ?? Emojis::kirby()->language()?->code() ?? 'en');
        } catch (Throwable) {
            return null;
        }

        // Stored emojis may lack the variation selector FE0F the data has (or the other way round)
        return $labels[$emoji]
            ?? $labels[$emoji . "\u{FE0F}"]
            ?? $labels[str_replace("\u{FE0F}", '', $emoji)]
            ?? null;
    }
}
