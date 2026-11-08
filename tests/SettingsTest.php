<?php

namespace KirbyEmojis\Tests;

use Kirby\Data\Data;
use KirbyEmojis\Emojibase;
use KirbyEmojis\Emojis;
use KirbyEmojis\Settings;

class SettingsTest extends TestCase
{
    public function testDefaults(): void
    {
        $settings = Emojis::settings();

        $this->assertSame('native', $settings->set());
        $this->assertSame([], $settings->categories());
        $this->assertSame('none', $settings->skinTone());
        $this->assertTrue($settings->skinToneSelector());
        $this->assertTrue($settings->suggestions());
        $this->assertTrue($settings->recentEmojis());
        $this->assertTrue($settings->customEmojis());
    }

    public function testSavedValuesOverrideOptions(): void
    {
        $this->app(['set' => 'fluent', 'custom' => ['enabled' => false]]);
        $this->settings(['emoji_set' => 'noto', 'custom_emojis' => true, 'skin_tone' => 'dark']);

        $this->assertSame('noto', Emojis::settings()->set());
        $this->assertTrue(Emojis::settings()->customEmojis());
        $this->assertSame('dark', Emojis::settings()->skinTone());
    }

    public function testSaveValidatesAndKeepsOtherValues(): void
    {
        $this->settings(['emoji_set' => 'noto', 'suggestions' => false]);

        Emojis::settings()->save([
            'categories' => ['flags', 'smileys-people', 'nope'],
            'skin_tone'  => 'purple',
            'unknown'    => 'value',
        ]);

        $saved = Data::read($this->root . '/site/config/emojis.yml');

        $this->assertSame('noto', $saved['emoji_set']);
        $this->assertFalse($saved['suggestions']);
        $this->assertSame(['smileys-people', 'flags'], $saved['categories']);
        $this->assertArrayNotHasKey('skin_tone', $saved);
        $this->assertArrayNotHasKey('unknown', $saved);
    }

    public function testInvalidSetIsNotSaved(): void
    {
        Emojis::settings()->save(['emoji_set' => 'unknown']);

        $this->assertArrayNotHasKey('emoji_set', Data::read($this->root . '/site/config/emojis.yml'));
    }

    public function testAllCategoriesMeanNoLimit(): void
    {
        $this->settings(['categories' => array_keys(Emojibase::CATEGORIES)]);
        $this->assertSame([], Emojis::settings()->categories());

        $this->settings(['categories' => ['flags']]);
        $this->assertSame(['flags'], Emojis::settings()->categories());
    }

    public function testBool(): void
    {
        foreach ([true, 'show', 'true', 1, '1'] as $value) {
            $this->assertTrue(Settings::bool($value, false));
        }

        foreach ([false, 'hide', 'false', 0, '0'] as $value) {
            $this->assertFalse(Settings::bool($value, true));
        }

        $this->assertTrue(Settings::bool(null, true));
    }

    public function testToScript(): void
    {
        $this->settings(['emoji_set' => 'openmoji', 'recent_emojis' => false]);

        $script = Emojis::settings()->toScript();

        $this->assertSame('openmoji', $script['set']);
        $this->assertSame('https://example.com/media/emojis', $script['imageUrl']);
        $this->assertFalse($script['recent']);
        $this->assertTrue($script['custom']);
    }

    public function testEmojibaseLocale(): void
    {
        $this->assertSame('de', Emojibase::locale('de_DE'));
        $this->assertSame('zh-hant', Emojibase::locale('zh-TW'));
        $this->assertSame('nb', Emojibase::locale('nn'));
        $this->assertSame('pt', Emojibase::locale('pt-BR'));
        $this->assertSame('en', Emojibase::locale('xx'));
        $this->assertSame('en', Emojibase::locale(null));
    }

    public function testEmojibaseDataIsFilteredAndCached(): void
    {
        $emojibase = Emojis::emojibase();

        $this->assertCount(2, $emojibase->data('en', ['smileys-people']));
        $this->assertCount(6, $emojibase->data('en'));

        $count = count($this->requests);
        $emojibase->data('en');
        $this->assertCount($count, $this->requests);

        $this->assertSame('grinsendes Gesicht', $emojibase->labels('de')['😀']);
        $this->assertSame('waving hand: medium-dark skin tone', $emojibase->labels('en')['👋🏾']);
    }
}
