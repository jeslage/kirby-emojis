<?php

namespace KirbyEmojis\Fields;

use Kirby\Reflection\Attributes\Prop;
use KirbyEmojis\Emojibase;
use KirbyEmojis\Panel;
use KirbyEmojis\Settings;

/**
 * Picker options for a field. Empty options fall back to the site-wide settings.
 */
trait PickerProps
{
    /**
     * Emoji categories to show in the picker, e.g. `[smileys-people, animals-nature]`
     */
    #[Prop(export: false)]
    protected array $categories = [];

    /**
     * Show custom emojis (Pro): `true` or `false`
     */
    #[Prop(export: false)]
    protected bool|string|null $customEmojis = null;

    /**
     * Default skin tone: none, light, medium-light, medium, medium-dark or dark
     */
    #[Prop(export: false)]
    protected string|null $skinTone = null;

    /**
     * Show the skin tone selector: `true` or `false`
     */
    #[Prop(export: false)]
    protected bool|string|null $skinToneSelector = null;

    /**
     * Picker options for the Panel: the site-wide config plus this field's overrides
     * (merged in the Panel like the settings precedence: field > site > options)
     */
    public function picker(): array
    {
        $categories = array_values(array_intersect(array_keys(Emojibase::CATEGORIES), $this->categories));
        $tone       = in_array($this->skinTone, Emojibase::SKIN_TONES, true) ? $this->skinTone : null;

        return [
            'site'  => Panel::config(),
            'field' => [
                'categories'       => $categories,
                'skinTone'         => $tone,
                'skinToneSelector' => $this->skinToneSelector === null ? null : Settings::bool($this->skinToneSelector, true),
                'custom'           => $this->customEmojis === null ? null : Settings::bool($this->customEmojis, true),
            ],
        ];
    }
}
