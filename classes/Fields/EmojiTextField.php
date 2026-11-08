<?php

namespace KirbyEmojis\Fields;

use Kirby\Form\Field\TextField;

/**
 * The core text field with an emoji picker that inserts at the cursor
 */
class EmojiTextField extends TextField
{
    use PickerProps;

    public function props(): array
    {
        return [
            ...parent::props(),
            'picker' => $this->picker(),
        ];
    }
}
