<?php

return [
    // Edition: null (automatic) or "free" to force the free edition, e.g. to try it locally
    'edition'   => null,
    'license'   => [
        // 'file'      => fn ($kirby) => $kirby->root('config') . '/.emojis-license',
        // 'validator' => KirbyEmojis\License\KeyFormatValidator::class,
    ],
    'emojibase' => [
        'url' => 'https://cdn.jsdelivr.net/npm/emojibase-data@18',
        // minutes
        'ttl' => 60 * 24 * 7,
    ],
    // Default emoji set when none is saved on the settings page: native, noto, fluent, openmoji or your own
    'set'          => 'native',
    // Extra or overriding image sets (Pro): ['name' => ['label' => …, 'url' => …, 'naming' => 'fluent|openmoji|noto|SetNaming class']]
    'sets'         => [],
    // Image sets to hide; ['*'] = native only
    'disabledSets' => [],
    'custom'       => [
        'enabled'  => true,
        // "site" or a page id; custom emojis are its files with the `emoji` template
        'parent'   => 'site',
        'template' => 'emoji',
    ],
    // File for the settings from the Panel's Emojis view
    // 'settings' => fn ($kirby) => $kirby->root('config') . '/emojis.yml',
    'cache' => [
        'emojibase' => true,
        'misses'    => true,
        'custom'    => true,
    ],
];
