<?php

use Kirby\Cms\File;
use KirbyEmojis\Emojis;

// Custom emojis are cached; refresh them when one of their files changes
$refresh = function (File|null $file = null, File|null $newFile = null, File|null $oldFile = null) {
    $custom = Emojis::custom();

    foreach ([$file, $newFile, $oldFile] as $candidate) {
        if ($candidate !== null && $custom->owns($candidate) === true) {
            $custom->clearCache();
            return;
        }
    }
};

return [
    'file.create:after'        => fn (File $file) => $refresh($file),
    'file.delete:after'        => fn (File $file) => $refresh($file),
    'file.changeName:after'    => fn (File $newFile, File $oldFile) => $refresh(null, $newFile, $oldFile),
    'file.replace:after'       => fn (File $newFile, File $oldFile) => $refresh(null, $newFile, $oldFile),
    'file.update:after'        => fn (File $newFile, File $oldFile) => $refresh(null, $newFile, $oldFile),
    'file.changeTemplate:after' => fn (File $newFile, File $oldFile) => $refresh(null, $newFile, $oldFile),
];
