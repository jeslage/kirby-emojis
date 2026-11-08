<?php

namespace KirbyEmojis\Values;

use JsonSerializable;
use KirbyEmojis\Emojis;
use Stringable;

/**
 * Text with emojis, plus its HTML with images for custom emojis and image sets
 */
class EmojiTextValue implements JsonSerializable, Stringable
{
    public function __construct(
        protected string $value,
        protected string $set,
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

    /**
     * The escaped text with images
     */
    public function html(): string
    {
        return Emojis::sets()->replace(htmlspecialchars($this->value, ENT_QUOTES), $this->set);
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'html'  => $this->html(),
            'set'   => $this->set,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
