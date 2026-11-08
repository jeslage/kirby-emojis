<?php

use Kirby\Content\Field;
use KirbyEmojis\Emojis;
use KirbyEmojis\Values\EmojiTextValue;
use KirbyEmojis\Values\EmojiValue;

return [
    /**
     * The field's text or HTML with emoji images (custom emojis, and Unicode emojis
     * for an image set). Pass a set name to override the site-wide set.
     * `$page->text()->kirbytext()->emojis()`
     */
    'emojis' => function (Field $field, string|null $set = null): Field {
        $field->value = Emojis::sets()->html($field->value(), $set);
        return $field;
    },

    /**
     * Emoji field value with label, image URL and HTML
     */
    'toEmoji' => function (Field $field, string|null $set = null): EmojiValue {
        return new EmojiValue(trim((string)$field->value()), Emojis::sets()->resolve($set));
    },

    /**
     * Emoji Text field value with its HTML
     */
    'toEmojiText' => function (Field $field, string|null $set = null): EmojiTextValue {
        return new EmojiTextValue((string)$field->value(), Emojis::sets()->resolve($set));
    },
];
