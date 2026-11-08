<?php

use Kirby\Cms\App;
use Kirby\Http\Response;
use KirbyEmojis\Emojibase;
use KirbyEmojis\Emojis;
use KirbyEmojis\EmojiSets;

return function (App $kirby) {
    $media = 'media';

    // Same as the core media route: only works if the media folder is inside the site
    if (str_starts_with($kirby->url('media'), $kirby->url('index')) === true) {
        $media = trim(substr($kirby->url('media'), strlen($kirby->url('index'))), '/');
    }

    $json = function (array $data) use ($kirby): Response {
        $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $etag = '"' . md5($body) . '"';

        $headers = [
            'Cache-Control' => 'private, max-age=86400',
            'ETag'          => $etag,
        ];

        if ($kirby->request()->header('If-None-Match') === $etag) {
            return new Response('', 'application/json', 304, $headers);
        }

        return new Response($body, 'application/json', 200, $headers);
    };

    $panelUser = function () use ($kirby): bool {
        return $kirby->user()?->role()->permissions()->for('access', 'panel') === true;
    };

    return [
        // Emojibase data for the Panel. Not under /api: frimousse fetches it without the CSRF header.
        // The categories are part of the path, as frimousse requests {url}/{locale}/data.json.
        [
            'pattern' => $media . '/emojis-data/shortcodes.json',
            'method'  => 'GET|HEAD',
            'action'  => function () use ($json, $panelUser) {
                if ($panelUser() === false) {
                    return new Response('', 'application/json', 403);
                }

                return $json(Emojis::emojibase()->shortcodes());
            },
        ],
        [
            'pattern' => $media . '/emojis-data/([a-z,-]+)/([a-z-]+)/(data|messages).json',
            'method'  => 'GET|HEAD',
            'action'  => function (string $categories, string $locale, string $file) use ($json, $panelUser) {
                if ($panelUser() === false) {
                    return new Response('', 'application/json', 403);
                }

                if (Emojibase::isLocale($locale) === false) {
                    return new Response('', 'application/json', 404);
                }

                $emojibase = Emojis::emojibase();

                return $json(match ($file) {
                    'data'     => $emojibase->data($locale, Emojibase::parseCategories($categories)),
                    'messages' => $emojibase->messages($locale),
                });
            },
        ],
        // Emoji set images: fetched once from the set's origin, then stored in the media
        // folder, so the web server serves later requests directly
        [
            'pattern' => $media . '/emojis/([a-z]+)/([0-9a-f-]+).svg',
            'action'  => function (string $set, string $codes) {
                $sets = Emojis::sets();

                if (preg_match(EmojiSets::CODES_PATTERN, $codes) !== 1 || $sets->isImageSet($set) === false) {
                    return new Response('Not found', 'text/plain', 404);
                }

                $svg      = $sets->svg($set, $codes);
                $fallback = $svg === null;

                return new Response($svg ?? $sets->fallbackSvg($codes), 'image/svg+xml', 200, [
                    'Cache-Control'           => $fallback ? 'public, max-age=3600' : 'public, max-age=2592000',
                    'X-Content-Type-Options'  => 'nosniff',
                    'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'",
                ]);
            },
        ],
    ];
};
