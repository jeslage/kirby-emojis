<?php

use Kirby\Exception\NotFoundException;
use Kirby\Exception\PermissionException;
use KirbyEmojis\Emojis;
use KirbyEmojis\Settings;

$canManage = function (): void {
    if (\KirbyEmojis\Panel::canManage() === false) {
        throw new PermissionException(message: 'You are not allowed to change the emoji settings');
    }
};

$imageSet = function (string $set): string {
    if (Emojis::sets()->isImageSet($set) === false) {
        throw new NotFoundException(message: 'Unknown emoji set: ' . $set);
    }

    return $set;
};

return [
    'routes' => [
        // Site-wide settings for Writer and Textarea, which have no field props of their own
        [
            'pattern' => 'emojis/config',
            'method'  => 'GET',
            'action'  => fn () => \KirbyEmojis\Panel::config(),
        ],
        [
            'pattern' => 'emojis/custom',
            'method'  => 'GET',
            'action'  => fn () => Emojis::custom()->toArray(),
        ],
        [
            'pattern' => 'emojis/settings',
            'method'  => 'POST',
            'action'  => function () use ($canManage) {
                $canManage();
                Emojis::settings()->save((array)$this->requestBody());
                Emojis::custom()->clearCache();

                return \KirbyEmojis\Panel::view();
            },
        ],
        [
            'pattern' => 'emojis/cache',
            'method'  => 'GET',
            'action'  => function () use ($canManage) {
                $canManage();
                return Emojis::sets()->cacheStatus();
            },
        ],
        [
            // Fetches a chunk of a set's images; the Panel loops until `done`
            'pattern' => 'emojis/cache/(:any)/warm',
            'method'  => 'POST',
            'action'  => function (string $set) use ($canManage, $imageSet) {
                $canManage();
                $set    = $imageSet($set);
                $codes  = Emojis::sets()->allCodes();
                $offset = max(0, (int)$this->requestBody('offset', 0));
                $limit  = min(100, max(1, (int)$this->requestBody('limit', 50)));
                $chunk  = array_slice($codes, $offset, $limit);
                $next   = $offset + count($chunk);

                return [
                    'next'   => $next,
                    'total'  => count($codes),
                    'done'   => $next >= count($codes),
                    'failed' => Emojis::sets()->warm($set, $chunk),
                ];
            },
        ],
        [
            'pattern' => 'emojis/cache/(:any)/clear',
            'method'  => 'POST',
            'action'  => function (string $set) use ($canManage, $imageSet) {
                $canManage();
                return ['deleted' => Emojis::sets()->clear($imageSet($set))];
            },
        ],
    ],
];
