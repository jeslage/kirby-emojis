# Kirby playground

A basic local Kirby 6 site for developing and testing the plugin. Only this README,
`setup.sh` and `seed/` are committed; the install itself is gitignored.

## Fresh install

```bash
cd playground
./setup.sh               # asks: local PHP or DDEV
./setup.sh --local       # or pick directly
./setup.sh --ddev
./setup.sh --no-seed     # blank Plainkit site without the test content
```

`setup.sh` creates a Kirby Plainkit (v6) here and links the plugin (this folder's parent) via a
Composer path repository. `getkirby/composer-installer` symlinks it to `site/plugins/kirby-emojis`,
so PHP changes apply immediately.

Open `/panel` and create the first user (the Panel installation is enabled in `site/config/config.php`).

### Local PHP

You need `php` (8.3+) and `composer` on your machine.

```bash
composer start           # http://localhost:8000/panel
```

### DDEV

You need [DDEV](https://ddev.com). The script creates and starts the DDEV project
`kirby-emojis`; the site runs at https://kirby-emojis.ddev.site (`ddev launch /panel`).

Prefix PHP and Composer commands with `ddev`, e.g. `ddev composer update`. The plugin folder is
mounted into the container at `/var/<plugin-folder>` (`.ddev/docker-compose.plugin.yaml`), so the
relative path repository and the `site/plugins` symlink also resolve inside the container.

```bash
ddev stop                      # stop the project
ddev delete -Oy kirby-emojis   # remove containers and database
```

## Panel assets (JS/CSS)

The Panel loads the plugin's `index.js` and `index.css`. Run these in the plugin root (`..`), Node 24+:

```bash
npm install
npm run dev     # kirbyup dev server: writes index.dev.js, which the Panel loads with hot reload
npm run build   # builds index.js and index.css (stop the dev server first, or delete index.dev.js)
```

## Test content (`seed/`)

Unless you pass `--no-seed`, `setup.sh` copies `seed/` over the fresh install. The folder mirrors the
site root:

| File                                    | Contents                                                          |
| --------------------------------------- | ----------------------------------------------------------------- |
| `site/config/config.php`                | Debug mode, Panel installation enabled                            |
| `site/config/emojis.yml`                | Settings of the Panel's Emojis view (Noto set)                    |
| `site/blueprints/pages/default.yml`     | Page blueprint with the test fields below                         |
| `site/templates/default.php`            | Outputs every field raw, with `->emojis()`, per set and as arrays |
| `content/home/home.txt`                 | Test values for every field                                       |
| `content/*.svg` + `*.svg.txt`           | Custom emojis `:sparkle-star:`, `:pixel-heart:`, `:approved:`     |

Fields in the `default` blueprint:

| Tab                  | Field                 | Type      | Tests                                                       |
| -------------------- | --------------------- | --------- | ----------------------------------------------------------- |
| Emoji fields         | `emoji`               | emoji     | default config                                              |
|                      | `emojiEmpty`          | emoji     | no value: "Select emoji" button                             |
|                      | `emojiConfigured`     | emoji     | only Smileys & People and Animals, skin tone `medium-dark`  |
|                      | `emojiReadOnly`       | emoji     | `disabled: true`                                            |
|                      | `emojiRequired`       | emoji     | validation, skin tone selector hidden                       |
|                      | `emojiText`           | emojitext | default config                                              |
|                      | `emojiTextConfigured` | emojitext | text field props (placeholder, maxlength, before), Food     |
|                      | `emojiTextRequired`   | emojitext | validation, skin tone selector hidden                       |
| Writer & Textarea    | `text`                | writer    | `emoji` node: toolbar button, custom emojis, suggestions    |
|                      | `textWithoutEmoji`    | writer    | control: no emoji button                                    |
|                      | `textarea`            | textarea  | `emoji` toolbar button and suggestions                      |
| Structure & Blocks   | `reactions`           | structure | emoji and emojitext columns                                 |
|                      | `blocks`              | blocks    | custom `emoji` block with both fields                       |

Run `kirby emojis:warm noto` (with [Kirby CLI](https://github.com/getkirby/cli)) or use
Panel → Emojis → Image Cache to fill the image cache.

To try the free edition, add `'jeslage.emojis.edition' => 'free'` to `site/config/config.php`. On
`localhost` Pro is available without a license key.

Content you change in the Panel stays local. Copy files back into `seed/` if they should be committed.

## Using an existing Kirby site instead

```bash
composer config repositories.kirby-emojis path ../path/to/kirby-emojis
composer require jeslage/kirby-emojis:@dev
```
