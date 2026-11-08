<?php

namespace KirbyEmojis\Tests;

use Kirby\Cms\App;
use Kirby\Data\Data;
use Kirby\Filesystem\Dir;
use Kirby\Filesystem\F;
use KirbyEmojis\Emojis;
use KirbyEmojis\EmojiSets;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected App $kirby;

    protected string $root;

    /** @var array<int, string> Requested URLs */
    protected array $requests = [];

    /** @var array<string, array{code: int, body: string}|null> URL (or suffix) => response */
    protected array $responses = [];

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/kirby-emojis-tests/' . uniqid();
        Dir::make($this->root . '/content');
        Dir::make($this->root . '/site/config');

        $this->requests  = [];
        $this->responses = [
            '/en/data.json'                  => ['code' => 200, 'body' => F::read(__DIR__ . '/fixtures/data.json')],
            '/de/data.json'                  => ['code' => 200, 'body' => str_replace('grinning face', 'grinsendes Gesicht', F::read(__DIR__ . '/fixtures/data.json'))],
            '/en/messages.json'              => ['code' => 200, 'body' => '{"groups":[]}'],
            '/en/shortcodes/emojibase.json'  => ['code' => 200, 'body' => '{"1F600":"grinning","1F44B":["wave","waving_hand"]}'],
        ];

        Emojis::$http = function (string $url, int $timeout) {
            $this->requests[] = $url;

            foreach ($this->responses as $suffix => $response) {
                if (str_ends_with($url, $suffix)) {
                    return $response;
                }
            }

            return ['code' => 404, 'body' => 'Not found'];
        };

        EmojiSets::flushExtended();
        $this->app();
    }

    protected function tearDown(): void
    {
        Emojis::$http = null;
        Emojis::flush();
        EmojiSets::flushExtended();
        Dir::remove($this->root);
    }

    /**
     * (Re)creates the Kirby instance with the given options for the plugin
     */
    protected function app(array $options = [], array $site = [], bool $pro = true): App
    {
        if ($pro === true) {
            Data::write($this->root . '/site/config/.emojis-license', ['key' => 'KEMO-TEST-TEST-TEST-TEST'], 'json');
        } else {
            F::remove($this->root . '/site/config/.emojis-license');
        }

        $this->kirby = new App([
            'roots' => [
                'index'   => $this->root,
                'content' => $this->root . '/content',
                'site'    => $this->root . '/site',
                'media'   => $this->root . '/media',
                'cache'   => $this->root . '/site/cache',
                'config'  => $this->root . '/site/config',
            ],
            'urls'    => ['index' => 'https://example.com'],
            'options' => [
                'jeslage.emojis' => $options,
            ],
            'site'    => $site,
        ]);

        Emojis::flush();

        return $this->kirby;
    }

    protected function settings(array $values): void
    {
        Data::write($this->root . '/site/config/emojis.yml', $values);
        Emojis::flush();
    }

    protected function svg(string $label = 'emoji'): array
    {
        return ['code' => 200, 'body' => '<svg xmlns="http://www.w3.org/2000/svg"><title>' . $label . '</title></svg>'];
    }
}
