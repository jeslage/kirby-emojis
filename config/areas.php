<?php

use Kirby\Cms\App;
use KirbyEmojis\Emojis;
use KirbyEmojis\License;
use KirbyEmojis\Panel;

return [
    'emojis' => fn (App $kirby) => [
        'label' => Emojis::t('custom.nav'),
        'icon'  => 'smile',
        'menu'  => fn () => Panel::canManage() ? true : 'disabled',
        'link'  => 'emojis',
        'views' => [
            [
                'pattern' => 'emojis',
                'action'  => fn () => [
                    'component' => 'k-emojis-view',
                    'title'     => Emojis::t('custom.nav'),
                    'props'     => Panel::view(),
                ],
            ],
        ],
        'dialogs' => [
            License::DIALOG => [
                'load' => fn () => [
                    'component' => 'k-form-dialog',
                    'props'     => [
                        'text'   => Emojis::t('license.text'),
                        'fields' => [
                            'key' => [
                                'type'        => 'text',
                                'label'       => Emojis::t('license.key'),
                                'help'        => Emojis::t('license.key_help'),
                                'placeholder' => 'KEMO-XXXX-XXXX-XXXX-XXXX',
                                'required'    => true,
                                'counter'     => false,
                                'font'        => 'monospace',
                            ],
                            'email' => [
                                'type'  => 'email',
                                'label' => Emojis::t('license.email'),
                            ],
                        ],
                        'submitButton' => [
                            'icon'  => 'key',
                            'text'  => Emojis::t('license.activate'),
                            'theme' => 'love',
                        ],
                        'value' => ['key' => '', 'email' => ''],
                    ],
                ],
                'submit' => function () use ($kirby) {
                    $request = $kirby->request();

                    Emojis::license()->activate(
                        (string)$request->get('key'),
                        $request->get('email') ?: null
                    );

                    return [
                        'message' => Emojis::t('license.activated'),
                        'reload'  => true,
                    ];
                },
            ],
        ],
    ],
];
