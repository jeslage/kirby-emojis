<?php

namespace KirbyEmojis\Tests;

use Kirby\Cms\App;
use KirbyEmojis\Emojis;
use KirbyEmojis\Panel;

class PermissionsTest extends TestCase
{
    protected function withEditor(bool $allowed): App
    {
        $this->kirby = $this->kirby->clone([
            'blueprints' => [
                'users/editor' => [
                    'name'        => 'editor',
                    'permissions' => ['jeslage.emojis' => ['settings' => $allowed]],
                ],
            ],
            'users' => [
                ['email' => 'editor@example.com', 'role' => 'editor'],
            ],
        ]);

        $this->kirby->impersonate('editor@example.com');
        Emojis::flush();

        return $this->kirby;
    }

    public function testAdminsCanManage(): void
    {
        $this->kirby->impersonate('kirby');
        $this->assertTrue(Panel::canManage());
    }

    public function testRolesCanBeDenied(): void
    {
        $this->withEditor(false);
        $this->assertFalse(Panel::canManage());

        $this->withEditor(true);
        $this->assertTrue(Panel::canManage());
    }
}
