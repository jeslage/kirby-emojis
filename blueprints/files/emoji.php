<?php

use KirbyEmojis\Emojis;

// Custom emoji images (Pro)
return fn () => [
    'title'  => Emojis::t('blueprint.title'),
    'accept' => [
        'extension' => 'svg, png, gif, webp, jpg, jpeg, avif',
        'maxsize'   => 1024 * 1024,
    ],
    'fields' => [
        'alt' => [
            'type'  => 'text',
            'label' => Emojis::t('blueprint.name'),
            'help'  => Emojis::t('blueprint.name_instructions'),
        ],
        'shortcode' => [
            'type'    => 'text',
            'label'   => Emojis::t('blueprint.shortcode'),
            'help'    => Emojis::t('blueprint.shortcode_instructions'),
            'pattern' => '^[a-z0-9_+-]+$',
            'before'  => ':',
            'after'   => ':',
            'width'   => '1/2',
        ],
        'keywords' => [
            'type'  => 'tags',
            'label' => Emojis::t('blueprint.keywords'),
            'help'  => Emojis::t('blueprint.keywords_instructions'),
            'width' => '1/2',
        ],
    ],
];
