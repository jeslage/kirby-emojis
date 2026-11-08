<?php

namespace KirbyEmojis\Tests;

use Kirby\Data\Data;
use KirbyEmojis\Emojis;

class FreeEditionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app(pro: false);
    }

    public function testFreeWithoutLicenseOutsideLocalEnvironments(): void
    {
        $this->assertFalse(Emojis::pro());
        $this->assertSame('free', Emojis::license()->edition());
    }

    public function testForcedFreeEdition(): void
    {
        $this->app(['edition' => 'free']);

        $this->assertFalse(Emojis::pro(), 'Even with a license');
    }

    public function testNoImageSets(): void
    {
        $this->settings(['emoji_set' => 'noto']);

        $this->assertSame(['native'], Emojis::sets()->sets());
        $this->assertSame('native', Emojis::sets()->default());
        $this->assertSame('Hi 😀', Emojis::sets()->html('Hi 😀'));
        $this->assertSame(404, $this->kirby->call('media/emojis/noto/1f600.svg')->code());
    }

    public function testNoCustomEmojis(): void
    {
        \Kirby\Filesystem\F::write($this->root . '/content/a.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');
        \Kirby\Filesystem\F::write($this->root . '/content/a.svg.txt', 'Template: emoji');
        $this->app(pro: false);

        $this->assertFalse(Emojis::settings()->customEmojis());
        $this->assertSame([], Emojis::custom()->all());
        $this->assertSame('Hi :a:', Emojis::sets()->html('Hi :a:'));
    }

    public function testSavingKeepsProValues(): void
    {
        $this->settings(['emoji_set' => 'noto', 'custom_emojis' => false]);

        Emojis::settings()->save(['skin_tone' => 'light']);

        $saved = Data::read($this->root . '/site/config/emojis.yml');
        $this->assertSame('noto', $saved['emoji_set']);
        $this->assertFalse($saved['custom_emojis']);
        $this->assertSame('light', $saved['skin_tone']);
    }

    public function testActivation(): void
    {
        try {
            Emojis::license()->activate('nope');
            $this->fail('Invalid keys are rejected');
        } catch (\Kirby\Exception\InvalidArgumentException) {
        }

        Emojis::license()->activate(' kemo-abcd-1234-efgh-5678 ', 'me@example.com');

        $this->assertTrue(Emojis::pro());
        $this->assertSame('KEMO-ABCD-1234-EFGH-5678', Data::read($this->root . '/site/config/.emojis-license', 'json')['key']);
    }

    public function testPluginLicenseStatus(): void
    {
        $plugin  = $this->kirby->plugin('jeslage/emojis');
        $license = $plugin->license();

        $this->assertSame('missing', $license->status()->value());
        $this->assertSame('emojis/license', $license->status()->dialog());

        Emojis::license()->activate('KEMO-ABCD-1234-EFGH-5678');
        $this->assertSame('active', Emojis::license()->toPluginLicense($plugin)->status()->value());
    }
}
