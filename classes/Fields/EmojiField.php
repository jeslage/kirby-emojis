<?php

namespace KirbyEmojis\Fields;

use Kirby\Form\Field\InputField;
use Kirby\Form\Mixin;
use KirbyEmojis\Emojis;

/**
 * Picks a single emoji, stored as a string (Unicode or a custom `:shortcode:`)
 */
class EmojiField extends InputField
{
    use Mixin\Icon;
    use PickerProps;

    protected string $value = '';

    public function fill(mixed $value): static
    {
        return parent::fill(is_string($value) ? trim($value) : '');
    }

    public function props(): array
    {
        return [
            ...parent::props(),
            'picker' => $this->picker(),
        ];
    }

    protected function validations(): array
    {
        return [
            'emoji' => function (mixed $value) {
                if ($value !== '' && mb_strlen($value) > 64) {
                    throw new \Kirby\Exception\InvalidArgumentException(message: Emojis::t('validation.emoji'));
                }
            },
        ];
    }
}
