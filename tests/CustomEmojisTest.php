<?php

namespace KirbyEmojis\Tests;

use Kirby\Filesystem\F;
use KirbyEmojis\CustomEmojis;
use KirbyEmojis\Emojis;

class CustomEmojisTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><rect width="10" height="10"/></svg>';
        F::write($this->root . '/content/party-parrot.svg', $svg);
        F::write($this->root . '/content/party-parrot.svg.txt', "Template: emoji\n\n----\n\nAlt: Party parrot\n\n----\n\nKeywords: bird, dance");
        F::write($this->root . '/content/check.svg', $svg);
        F::write($this->root . '/content/check.svg.txt', "Template: emoji\n\n----\n\nShortcode: approved");
        F::write($this->root . '/content/logo.svg', $svg);

        $this->app();
    }

    public function testList(): void
    {
        $all = Emojis::custom()->all();

        $this->assertSame(['approved', 'party-parrot'], array_keys($all));
        $this->assertSame('Party parrot', $all['party-parrot']['label']);
        $this->assertSame(['bird', 'dance'], $all['party-parrot']['keywords']);
        $this->assertSame('Approved', $all['approved']['label'], 'Label from the shortcode without alt text');
        $this->assertStringStartsWith('https://example.com/media/site/', $all['approved']['url']);
    }

    public function testNormalize(): void
    {
        $this->assertSame('party-parrot', CustomEmojis::normalize(':Party Parrot:'));
        $this->assertSame('cafe', CustomEmojis::normalize('Café'));
        $this->assertSame('a_b+c', CustomEmojis::normalize('a_b+c'));
    }

    public function testReplace(): void
    {
        $html = Emojis::custom()->replace('Yay :party-parrot: and :unknown: and :approved:');

        $this->assertSame(2, substr_count($html, 'class="emoji emoji-custom"'));
        $this->assertStringContainsString(':unknown:', $html);
        $this->assertStringContainsString('alt="Party parrot" title=":party-parrot:"', $html);
    }

    public function testReplaceInEverySet(): void
    {
        foreach (['native', 'noto'] as $set) {
            $html = Emojis::sets()->replace('<p>:party-parrot: 😀</p>', $set);
            $this->assertStringContainsString('emoji-custom', $html);
        }
    }

    public function testWriterNodeIsRendered(): void
    {
        $html = Emojis::sets()->html('<p>Hi <span data-emoji="approved" class="k-emoji">:approved:</span></p>', 'native');

        $this->assertStringContainsString('<span data-emoji="approved" class="k-emoji"><img class="emoji emoji-custom"', $html);
    }

    public function testFilesChangedOutsideThePanelArePickedUp(): void
    {
        $this->assertCount(2, Emojis::custom()->all());

        F::write($this->root . '/content/new.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');
        F::write($this->root . '/content/new.svg.txt', 'Template: emoji');
        $this->app();

        $this->assertCount(3, Emojis::custom()->all());
    }

    public function testCacheIsClearedWhenAFileIsUploaded(): void
    {
        $this->assertCount(2, Emojis::custom()->all());

        $this->kirby->impersonate('kirby');
        $source = $this->root . '/upload.svg';
        F::write($source, '<svg xmlns="http://www.w3.org/2000/svg"/>');

        $this->kirby->site()->createFile(['source' => $source, 'filename' => 'uploaded.svg', 'template' => 'emoji']);

        $this->assertArrayHasKey('uploaded', Emojis::custom()->all());
    }

    public function testOtherParent(): void
    {
        $this->app(['custom' => ['parent' => 'emojis']]);
        $this->assertSame([], Emojis::custom()->all(), 'Missing parent page');
        $this->assertFalse(Emojis::custom()->isEnabled());
    }

    public function testDisabled(): void
    {
        $this->app(['custom' => ['enabled' => false]]);
        $this->assertSame([], Emojis::custom()->all());

        $this->app();
        $this->settings(['custom_emojis' => false]);
        $this->assertSame([], Emojis::custom()->all());
    }

    public function testCustomEmojiValue(): void
    {
        $page = new \Kirby\Cms\Page(['slug' => 'test', 'content' => ['emoji' => ':party-parrot:']]);
        $value = $page->emoji()->toEmoji()->toArray();

        $this->assertTrue($value['custom']);
        $this->assertSame('party-parrot', $value['shortcode']);
        $this->assertSame('Party parrot', $value['label']);
        $this->assertNotNull($value['url']);
    }
}
