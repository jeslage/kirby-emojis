<?php

use Kirby\Cms\App as Kirby;
use KirbyEmojis\Emojis;
use KirbyEmojis\Fields\EmojiField;
use KirbyEmojis\Fields\EmojiTextField;

// Without Composer (Git submodule or ZIP download). Not the plugin's own vendor/autoload.php:
// in development it contains a second copy of Kirby.
if (class_exists(Emojis::class) === false) {
    spl_autoload_register(function (string $class) {
        if (str_starts_with($class, 'KirbyEmojis\\') === true) {
            $file = __DIR__ . '/classes/' . str_replace('\\', '/', substr($class, 12)) . '.php';

            if (is_file($file) === true) {
                require $file;
            }
        }
    });
}

$translations = [];

foreach (['en', 'de', 'es', 'fr'] as $locale) {
    $translations[$locale] = require __DIR__ . '/translations/' . $locale . '.php';
}

Kirby::plugin(
    name: Emojis::PLUGIN,
    license: fn ($plugin) => Emojis::license()->toPluginLicense($plugin),
    extends: [
        'options'      => require __DIR__ . '/config/options.php',
        'api'          => require __DIR__ . '/config/api.php',
        'routes'       => require __DIR__ . '/config/routes.php',
        'areas'        => require __DIR__ . '/config/areas.php',
        'hooks'        => require __DIR__ . '/config/hooks.php',
        'fieldMethods' => require __DIR__ . '/config/fieldMethods.php',
        'commands'     => require __DIR__ . '/config/commands.php',
        'blueprints'   => [
            'files/emoji' => require __DIR__ . '/blueprints/files/emoji.php',
        ],
        'fields'       => [
            'emoji'     => EmojiField::class,
            'emojitext' => EmojiTextField::class,
        ],
        'permissions'  => [
            'settings' => true,
        ],
        'translations' => $translations,
    ]
);
