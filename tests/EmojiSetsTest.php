<?php

namespace KirbyEmojis\Tests;

use Kirby\Filesystem\F;
use KirbyEmojis\Contracts\SetNaming;
use KirbyEmojis\Emojis;
use KirbyEmojis\EmojiSets;
use PHPUnit\Framework\Attributes\DataProvider;

class EmojiSetsTest extends TestCase
{
    public function testCodepoints(): void
    {
        $sets = Emojis::sets();

        $this->assertSame('1f600', $sets->codepoints('😀'));
        $this->assertSame('2764-fe0f', $sets->codepoints('❤️'));
        $this->assertSame('1f469-1f3fd-200d-1f4bb', $sets->codepoints('👩🏽‍💻'));
        $this->assertSame('👩🏽‍💻', $sets->emojiFromCodes('1f469-1f3fd-200d-1f4bb'));
    }

    public static function candidateProvider(): array
    {
        return [
            'fluent'            => ['fluent', '1f600', ['1f600.svg']],
            'fluent fe0f'       => ['fluent', '2764-fe0f', ['2764-fe0f.svg', '2764.svg']],
            'fluent keycap'     => ['fluent', '31-fe0f-20e3', ['0031-fe0f-20e3.svg', '0031-20e3.svg', '31-fe0f-20e3.svg', '31-20e3.svg']],
            'openmoji'          => ['openmoji', '1f600', ['1F600.svg']],
            'openmoji keycap'   => ['openmoji', '31-fe0f-20e3', ['0031-FE0F-20E3.svg', '0031-20E3.svg', '31-FE0F-20E3.svg', '31-20E3.svg']],
            'noto'              => ['noto', '1f600', ['2D/svg/emoji_u1f600.svg']],
            'noto flag'         => ['noto', '1f1e9-1f1ea', ['third_party/region-flags/waved-svg/emoji_u1f1e9_1f1ea.svg']],
            'noto subdivision'  => ['noto', '1f3f4-e0067-e0062-e0073-e0063-e0074-e007f', ['third_party/region-flags/waved-svg/emoji_u1f3f4_e0067_e0062_e0073_e0063_e0074_e007f.svg']],
            'noto zwj with fe0f' => ['noto', '1f3f3-fe0f-200d-1f308', ['2D/svg/emoji_u1f3f3_fe0f_200d_1f308.svg', '2D/svg/emoji_u1f3f3_200d_1f308.svg']],
        ];
    }

    #[DataProvider('candidateProvider')]
    public function testCandidates(string $set, string $codes, array $expected): void
    {
        $this->assertSame($expected, Emojis::sets()->candidates($set, $codes));
    }

    public function testBuiltInSets(): void
    {
        $this->assertSame(['native', 'noto', 'fluent', 'openmoji'], Emojis::sets()->sets());
        $this->assertSame('native', Emojis::sets()->default());
    }

    public function testConfiguredSetsAndOverrides(): void
    {
        $this->app([
            'sets' => [
                'twemoji' => ['label' => 'Twemoji', 'url' => 'https://cdn.example.com/twemoji/'],
                'noto'    => ['label' => 'Noto (mirror)'],
                'Bad-Name' => ['url' => 'https://example.com'],
                'nourl'   => ['label' => 'No URL'],
            ],
        ]);

        $sets = Emojis::sets();

        $this->assertSame(['native', 'noto', 'fluent', 'openmoji', 'twemoji'], $sets->sets());
        $this->assertSame('Noto (mirror)', $sets->label('noto'));
        $this->assertStringContainsString('googlefonts/noto-emoji', $sets->definition('noto')['url']);
        $this->assertSame('Twemoji', $sets->label('twemoji'));
    }

    public function testExtendAndDisable(): void
    {
        EmojiSets::extend('custom', ['url' => 'https://example.com/custom']);
        $this->app(['disabledSets' => ['fluent']]);

        $this->assertSame(['native', 'noto', 'openmoji', 'custom'], Emojis::sets()->sets());

        $this->app(['disabledSets' => ['*']]);
        $this->assertSame(['native'], Emojis::sets()->sets());
    }

    public function testCustomNamingClass(): void
    {
        EmojiSets::extend('shouty', ['url' => 'https://example.com/shouty', 'naming' => ShoutyNaming::class]);
        Emojis::flush();

        $this->assertSame(['1F600.SVG'], Emojis::sets()->candidates('shouty', '1f600'));

        // Custom namings get the codepoints unpadded
        $this->assertSame(['31-FE0F-20E3.SVG', '31-20E3.SVG'], Emojis::sets()->candidates('shouty', '31-fe0f-20e3'));
    }

    public function testDefaultFromSettings(): void
    {
        $this->settings(['emoji_set' => 'noto']);
        $this->assertSame('noto', Emojis::sets()->default());

        $this->settings(['emoji_set' => 'unknown']);
        $this->assertSame('native', Emojis::sets()->default());

        $this->app(['set' => 'openmoji']);
        $this->settings([]);
        $this->assertSame('openmoji', Emojis::sets()->default());
    }

    public function testSvgIsFetchedStoredAndServedFromDisk(): void
    {
        $this->responses['/2D/svg/emoji_u1f600.svg'] = $this->svg('grinning');

        $sets = Emojis::sets();
        $svg  = $sets->svg('noto', '1f600');

        $this->assertStringContainsString('grinning', $svg);
        $this->assertFileExists($this->root . '/media/emojis/noto/1f600.svg');
        $this->assertTrue($sets->isCached('noto', '1f600'));

        $count = count($this->requests);
        $sets->svg('noto', '1f600');
        $this->assertCount($count, $this->requests);
    }

    public function testSvgTriesCandidatesAndRemembersMisses(): void
    {
        $this->responses['/2764.svg'] = $this->svg('heart');

        $this->assertStringContainsString('heart', Emojis::sets()->svg('fluent', '2764-fe0f'));
        $this->assertNull(Emojis::sets()->svg('fluent', '1f9cc'));

        $count = count($this->requests);
        $this->assertNull(Emojis::sets()->svg('fluent', '1f9cc'));
        $this->assertCount($count, $this->requests, 'A miss is remembered');
    }

    public function testNetworkErrorsAreNotRememberedAsMisses(): void
    {
        $fetch = Emojis::$http;
        Emojis::$http = fn () => null;
        $this->assertNull(Emojis::sets()->svg('fluent', '1f600'));

        Emojis::$http = $fetch;
        $this->responses['/1f600.svg'] = $this->svg();
        $this->assertNotNull(Emojis::sets()->svg('fluent', '1f600'));
    }

    public function testNonSvgResponsesAreRejected(): void
    {
        $this->responses['/1f600.svg'] = ['code' => 200, 'body' => '<html>Error page</html>'];

        $this->assertNull(Emojis::sets()->svg('fluent', '1f600'));
    }

    public function testWarmAndClear(): void
    {
        $this->responses['/1f600.svg'] = $this->svg();
        $this->responses['/1f44b.svg'] = $this->svg();

        $failed = Emojis::sets()->warm('fluent', ['1f600', '1f44b', '1f9cc']);
        $this->assertSame(['1f9cc'], $failed);

        $status = Emojis::sets()->cacheStatus();
        $this->assertSame(['noto', 'fluent', 'openmoji'], array_column($status, 'set'));
        $this->assertSame(2, $status[1]['cached']);
        $this->assertSame(7, $status[1]['total'], 'Every emoji and skin tone of the data');

        $this->assertSame(2, Emojis::sets()->clear('fluent'));
        $this->assertFalse(Emojis::sets()->isCached('fluent', '1f600'));

        // misses are forgotten as well
        $this->responses['/1f9cc.svg'] = $this->svg();
        $this->assertNotNull(Emojis::sets()->svg('fluent', '1f9cc'));
    }

    public function testReplace(): void
    {
        $sets = Emojis::sets();

        $html = $sets->replace('<p title="😀">Hi 😀 and 🇩🇪 and 1️⃣ and 🏴󠁧󠁢󠁳󠁣󠁴󠁿</p>', 'noto');

        $this->assertStringContainsString('<p title="😀">', $html, 'Attributes stay untouched');
        $this->assertStringContainsString('src="https://example.com/media/emojis/noto/1f600.svg"', $html);
        $this->assertStringContainsString('/noto/1f1e9-1f1ea.svg', $html);
        $this->assertStringContainsString('/noto/31-fe0f-20e3.svg', $html);
        $this->assertStringContainsString('/noto/1f3f4-e0067-e0062-e0073-e0063-e0074-e007f.svg', $html);
        $this->assertSame(4, substr_count($html, '<img'));
    }

    public function testReplaceWithNativeSetKeepsText(): void
    {
        $this->assertSame('Hi 😀', Emojis::sets()->replace('Hi 😀', 'native'));
    }

    public function testImageRoute(): void
    {
        $this->responses['/1f600.svg'] = $this->svg('fluent grinning');

        $response = $this->kirby->call('media/emojis/fluent/1f600.svg');
        $this->assertSame(200, $response->code());
        $this->assertSame('image/svg+xml', $response->type());
        $this->assertStringContainsString('fluent grinning', $response->body());
        $this->assertSame('public, max-age=2592000', $response->headers()['Cache-Control']);

        $fallback = $this->kirby->call('media/emojis/fluent/1f9cc.svg');
        $this->assertSame(200, $fallback->code());
        $this->assertStringContainsString('<text', $fallback->body());
        $this->assertSame('public, max-age=3600', $fallback->headers()['Cache-Control']);

        $this->assertSame(404, $this->kirby->call('media/emojis/native/1f600.svg')->code());
        $this->assertSame(404, $this->kirby->call('media/emojis/unknown/1f600.svg')->code());
    }

    public function testFieldMethods(): void
    {
        $this->settings(['emoji_set' => 'openmoji']);

        $page = new \Kirby\Cms\Page(['slug' => 'test', 'content' => ['emoji' => '😀', 'text' => 'Hi 👋']]);

        $this->assertStringContainsString('/openmoji/1f44b.svg', $page->text()->emojis()->value());
        $this->assertStringContainsString('/noto/1f44b.svg', $page->text()->emojis('noto')->value());
        $this->assertSame('Hi 👋', $page->text()->emojis('native')->value());

        $emoji = $page->emoji()->toEmoji();
        $this->assertSame('😀', (string)$emoji);
        $this->assertSame([
            'value'     => '😀',
            'label'     => 'grinning face',
            'url'       => 'https://example.com/media/emojis/openmoji/1f600.svg',
            'set'       => 'openmoji',
            'custom'    => false,
            'shortcode' => null,
        ], $emoji->toArray());

        $text = $page->text()->toEmojiText()->toArray();
        $this->assertSame('Hi 👋', $text['value']);
        $this->assertStringContainsString('<img', $text['html']);
    }

    public function testLabelsMatchWithAndWithoutVariationSelector(): void
    {
        $page = new \Kirby\Cms\Page(['slug' => 'test', 'content' => ['emoji' => '❤']]);

        $this->assertSame('red heart', $page->emoji()->toEmoji()->label());
    }
}

class ShoutyNaming implements SetNaming
{
    public function files(array $codepoints): array
    {
        return [strtoupper(implode('-', $codepoints)) . '.SVG'];
    }
}
