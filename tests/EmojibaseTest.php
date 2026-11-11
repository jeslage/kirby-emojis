<?php

namespace KirbyEmojis\Tests;

use KirbyEmojis\Emojis;
use Throwable;

class EmojibaseTest extends TestCase
{
    protected function failingRequests(): int
    {
        return count(array_filter($this->requests, fn ($url) => str_ends_with($url, '/fr/data.json')));
    }

    public function testFailedFetchesAreRemembered(): void
    {
        $this->responses['/fr/data.json'] = ['code' => 503, 'body' => ''];

        foreach (range(1, 3) as $i) {
            try {
                Emojis::emojibase()->data('fr');
                $this->fail('The fetch fails');
            } catch (Throwable $e) {
                $this->assertStringNotContainsString('The fetch fails', $e->getMessage());
            }
        }

        $this->assertSame(1, $this->failingRequests(), 'Only the first call waits for the CDN');
    }

    public function testInvalidJsonIsRememberedAsAFailure(): void
    {
        $this->responses['/fr/data.json'] = ['code' => 200, 'body' => '<html>CDN error page</html>'];

        foreach (range(1, 2) as $i) {
            try {
                Emojis::emojibase()->data('fr');
            } catch (Throwable) {
            }
        }

        $this->assertSame(1, $this->failingRequests());
    }

    public function testClearRetriesRightAway(): void
    {
        $this->responses['/fr/data.json'] = ['code' => 503, 'body' => ''];

        try {
            Emojis::emojibase()->data('fr');
        } catch (Throwable) {
        }

        $this->responses['/fr/data.json'] = $this->responses['/en/data.json'];
        Emojis::emojibase()->clear();

        $this->assertNotEmpty(Emojis::emojibase()->data('fr'));
        $this->assertSame(2, $this->failingRequests());
    }

    public function testLabelsDontWaitForAFailingCdn(): void
    {
        $this->responses['/fr/data.json'] = ['code' => 503, 'body' => ''];
        $page = new \Kirby\Cms\Page(['slug' => 'test', 'content' => ['emoji' => '😀']]);

        $this->assertNull((new \KirbyEmojis\Values\EmojiValue('😀', 'native', 'fr'))->label());
        $this->assertNull((new \KirbyEmojis\Values\EmojiValue('😀', 'native', 'fr'))->label());
        $this->assertSame(1, $this->failingRequests());
        $this->assertSame('😀', (string)$page->emoji()->toEmoji());
    }
}
