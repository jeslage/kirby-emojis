<?php

use KirbyEmojis\Emojis;

/** @var Kirby\Cms\Page $page */
$fields = ['emoji', 'emojiEmpty', 'emojiConfigured', 'emojiRequired', 'emojiText', 'emojiTextConfigured', 'text', 'textarea'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $page->title()->esc() ?> · Kirby Emojis</title>
  <style>
    body { max-width: 60rem; margin: 2rem auto; padding: 0 1rem; font: 16px/1.5 system-ui, sans-serif; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: .5rem; border-bottom: 1px solid #ddd; text-align: left; vertical-align: top; }
    th { width: 12rem; }
    img.emoji { width: 1.25em; height: 1.25em; vertical-align: -0.25em; }
    code { font-size: .8rem; }
  </style>
</head>
<body>
  <h1><?= $page->title()->esc() ?></h1>
  <p>Edition: <strong><?= Emojis::license()->edition() ?></strong> · Site set: <strong><?= Emojis::sets()->default() ?></strong></p>

  <h2>Fields</h2>
  <table>
    <tr><th>Field</th><th>Stored</th><th><code>->emojis()</code></th></tr>
    <?php foreach ($fields as $name): ?>
    <tr>
      <th><?= $name ?></th>
      <td><?= $page->content()->get($name)->esc() ?></td>
      <td><?= $page->content()->get($name)->emojis() ?></td>
    </tr>
    <?php endforeach ?>
  </table>

  <h2>Per set</h2>
  <table>
    <?php foreach (Emojis::sets()->sets() as $set): ?>
    <tr>
      <th><?= Emojis::sets()->label($set) ?></th>
      <td><?= $page->emojiText()->emojis($set) ?></td>
    </tr>
    <?php endforeach ?>
  </table>

  <h2><code>->toEmoji()</code></h2>
  <pre><?= htmlspecialchars(json_encode($page->emoji()->toEmoji(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>

  <h2>Structure</h2>
  <ul>
    <?php foreach ($page->reactions()->toStructure() as $reaction): ?>
    <li><?= $reaction->emoji()->toEmoji()->html() ?> <?= $reaction->note()->emojis() ?></li>
    <?php endforeach ?>
  </ul>

  <h2>Custom emojis</h2>
  <ul>
    <?php foreach (Emojis::custom()->all() as $emoji): ?>
    <li><img class="emoji" src="<?= $emoji['url'] ?>" alt="<?= esc($emoji['label'], 'attr') ?>"> <code>:<?= $emoji['shortcode'] ?>:</code> <?= esc($emoji['label']) ?></li>
    <?php endforeach ?>
  </ul>
</body>
</html>
