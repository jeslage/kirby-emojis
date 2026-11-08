<?php

namespace KirbyEmojis\Tests;

use Kirby\Cms\Page;
use Kirby\Form\Form;
use KirbyEmojis\Emojis;

class FieldsTest extends TestCase
{
    protected function form(array $fields, array $values = []): Form
    {
        $page = new Page(['slug' => 'test']);
        return (new Form(fields: $fields, model: $page))->fill(input: $values);
    }

    public function testEmojiField(): void
    {
        $form  = $this->form(['emoji' => ['type' => 'emoji']], ['emoji' => ' 😀 ']);
        $field = $form->field('emoji');

        $this->assertSame('😀', $field->toStoredValue());

        $props = $field->props();
        $this->assertSame('emoji', $props['type']);
        $this->assertSame('native', $props['picker']['site']['set']);
        $this->assertSame([], $props['picker']['field']['categories']);
        $this->assertNull($props['picker']['field']['skinToneSelector']);
    }

    public function testFieldOverrides(): void
    {
        $field = $this->form(['emoji' => [
            'type'             => 'emoji',
            'categories'       => ['flags', 'nope'],
            'skinTone'         => 'medium-dark',
            'skinToneSelector' => false,
            'customEmojis'     => 'hide',
        ]])->field('emoji');

        $this->assertSame([
            'categories'       => ['flags'],
            'skinTone'         => 'medium-dark',
            'skinToneSelector' => false,
            'custom'           => false,
        ], $field->props()['picker']['field']);
    }

    public function testEmojiTextFieldInheritsTextField(): void
    {
        $field = $this->form(['note' => [
            'type'        => 'emojitext',
            'placeholder' => 'Say hi',
            'maxlength'   => 20,
            'before'      => '@',
        ]], ['note' => 'Hi 👋'])->field('note');

        $props = $field->props();

        $this->assertSame('Hi 👋', $field->toStoredValue());
        $this->assertSame('Say hi', $props['placeholder']);
        $this->assertSame(20, $props['maxlength']);
        $this->assertSame('@', $props['before']);
        $this->assertArrayHasKey('picker', $props);
    }

    public function testRequiredEmojiField(): void
    {
        $field = $this->form(['emoji' => ['type' => 'emoji', 'required' => true]])->field('emoji');

        $this->assertArrayHasKey('required', $field->errors());
    }

    public function testDataRouteNeedsPanelUser(): void
    {
        $this->assertSame(403, $this->kirby->call('media/emojis-data/all/en/data.json')->code());

        $this->kirby->impersonate('kirby');
        $response = $this->kirby->call('media/emojis-data/smileys-people/en/data.json');

        $this->assertSame(200, $response->code());
        $this->assertCount(2, json_decode($response->body(), true));
        $this->assertSame(404, $this->kirby->call('media/emojis-data/all/xx/data.json')->code());

        $etag = $response->headers()['ETag'];
        $_SERVER['HTTP_IF_NONE_MATCH'] = $etag;
        $this->app();
        $this->kirby->impersonate('kirby');
        $this->assertSame(304, $this->kirby->call('media/emojis-data/smileys-people/en/data.json')->code());
        unset($_SERVER['HTTP_IF_NONE_MATCH']);
    }
}
