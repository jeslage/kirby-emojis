<?php

namespace KirbyEmojis;

use Kirby\Cms\Page;
use Kirby\Form\Form;

/**
 * Data for the Panel: the site-wide picker config and the Emojis view
 */
class Panel
{
    /**
     * Site-wide picker config, also used by Writer and Textarea
     */
    public static function config(): array
    {
        return [
            ...Emojis::settings()->toScript(),
            'dataUrl' => static::dataUrl(),
        ];
    }

    public static function dataUrl(): string
    {
        return rtrim(Emojis::kirby()->url('media'), '/') . '/emojis-data';
    }

    public static function canManage(): bool
    {
        return Emojis::kirby()->user()?->role()->permissions()->for('jeslage.emojis', 'settings') === true;
    }

    /**
     * Props for the Emojis view
     */
    public static function view(): array
    {
        $pro    = Emojis::pro();
        $custom = Emojis::custom();
        $parent = $custom->parent();

        return [
            'pro'       => $pro,
            'proUrl'    => Emojis::PRO_URL,
            'canManage' => static::canManage(),
            'license'   => [
                'edition'   => Emojis::license()->edition(),
                'activated' => Emojis::license()->isActivated(),
                'dialog'    => License::DIALOG,
            ],
            'fields'    => static::form()->toProps(),
            'values'    => Emojis::settings()->toForm(),
            'custom'    => [
                'list'      => $pro && $parent !== null ? array_values($custom->load()) : [],
                'uploadUrl' => $parent === null ? null : static::filesApi($parent),
                'parentUrl' => $parent?->panel()->url(true),
                'template'  => $custom->template(),
            ],
            'cache'     => $pro ? Emojis::sets()->cacheStatus() : [],
        ];
    }

    /**
     * Settings form, as fields for `k-form`
     */
    public static function form(): Form
    {
        $pro        = Emojis::pro();
        $categories = [];
        $tones      = [];

        foreach (array_keys(Emojibase::CATEGORIES) as $key) {
            $categories[] = ['value' => $key, 'text' => Emojis::t('categories.' . $key)];
        }

        foreach (Emojibase::SKIN_TONES as $tone) {
            $tones[] = ['value' => $tone, 'text' => Emojis::t('skin_tones.' . $tone)];
        }

        $fields = [];

        if ($pro === true) {
            $sets = [];

            foreach (Emojis::sets()->options() as $value => $text) {
                $sets[] = ['value' => $value, 'text' => $text];
            }

            $fields['emoji_set'] = [
                'type'    => 'select',
                'label'   => Emojis::t('config.emoji_set'),
                'help'    => Emojis::t('settings.emoji_set_instructions'),
                'options' => $sets,
                'empty'   => false,
                'width'   => '1/2',
            ];
        }

        $fields['picker'] = [
            'type'  => 'headline',
            'label' => Emojis::t('settings.section_picker'),
            'help'  => Emojis::t('settings.section_picker_instructions'),
        ];

        $fields['categories'] = [
            'type'     => 'checkboxes',
            'label'    => Emojis::t('config.categories'),
            'options'  => $categories,
            'columns'  => 2,
            'required' => true,
        ];

        $fields['skin_tone'] = [
            'type'    => 'select',
            'label'   => Emojis::t('config.skin_tone'),
            'options' => $tones,
            'empty'   => false,
            'width'   => '1/2',
        ];

        $fields['skin_tone_selector'] = [
            'type'  => 'toggle',
            'label' => Emojis::t('config.skin_tone_selector'),
            'width' => '1/2',
        ];

        $fields['suggestions'] = [
            'type'  => 'toggle',
            'label' => Emojis::t('settings.suggestions'),
            'help'  => Emojis::t('settings.suggestions_instructions'),
            'width' => '1/2',
        ];

        $fields['recent_emojis'] = [
            'type'  => 'toggle',
            'label' => Emojis::t('settings.recent_emojis'),
            'help'  => Emojis::t('settings.recent_emojis_instructions'),
            'width' => '1/2',
        ];

        if ($pro === true) {
            $fields['custom_emojis'] = [
                'type'  => 'toggle',
                'label' => Emojis::t('config.custom_emojis'),
                'help'  => Emojis::t('settings.custom_setup'),
            ];
        }

        return new Form(fields: $fields, model: Emojis::kirby()->site());
    }

    /**
     * API path to upload files to the custom emoji parent
     */
    protected static function filesApi(mixed $parent): string
    {
        if ($parent instanceof Page) {
            return 'pages/' . str_replace('/', '+', $parent->id()) . '/files';
        }

        return 'site/files';
    }
}
