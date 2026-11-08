<?php

use KirbyEmojis\Emojis;

return [
    'emojis:warm' => [
        'description' => 'Fetches all images of an emoji set (Pro)',
        'args'        => [
            'set' => [
                'description' => 'Set name (default: the site-wide set)',
            ],
        ],
        'command'     => function ($cli) {
            if (Emojis::pro() === false) {
                $cli->error('Emoji sets are a Pro feature.');
                return;
            }

            $sets = Emojis::sets();
            $set  = $cli->arg('set') ?: $sets->default();

            if ($sets->isImageSet($set) === false) {
                $cli->error('"' . $set . '" is not an image set. Available: ' . implode(', ', array_slice($sets->sets(), 1)));
                return;
            }

            $codes  = $sets->allCodes();
            $failed = [];
            $total  = count($codes);

            foreach (array_chunk($codes, 50) as $index => $chunk) {
                $failed = [...$failed, ...$sets->warm($set, $chunk)];
                $cli->out(sprintf('%d/%d', min(($index + 1) * 50, $total), $total));
            }

            $cli->success(sprintf('Cached %d emojis of "%s".', $total - count($failed), $set));

            if ($failed !== []) {
                $cli->out(count($failed) . ' are not in this set and fall back to native emojis.');
            }
        },
    ],
    'emojis:clear' => [
        'description' => 'Clears cached emoji set images and/or the emoji data',
        'args'        => [
            'set' => [
                'description' => 'Set name (default: all sets)',
            ],
            'data' => [
                'longPrefix'  => 'data',
                'description' => 'Also clear the cached Emojibase data',
                'noValue'     => true,
            ],
        ],
        'command'     => function ($cli) {
            $sets = Emojis::sets();
            $set  = $cli->arg('set');

            if ($cli->arg('data')) {
                Emojis::emojibase()->clear();
                $cli->success('Cleared the emoji data.');
            }

            if (Emojis::pro() === false) {
                if ($set || !$cli->arg('data')) {
                    $cli->error('Emoji sets are a Pro feature.');
                }

                return;
            }

            if ($set && $sets->isImageSet($set) === false) {
                $cli->error('"' . $set . '" is not an image set.');
                return;
            }

            foreach ($set ? [$set] : array_slice($sets->sets(), 1) as $name) {
                $cli->success(sprintf('Cleared %d images of "%s".', $sets->clear($name), $name));
            }
        },
    ],
];
