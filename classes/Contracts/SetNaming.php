<?php

namespace KirbyEmojis\Contracts;

/**
 * Maps an emoji's codepoints to the file names of a custom image set
 */
interface SetNaming
{
    /**
     * @param array<int, string> $codepoints lowercase hex codepoints
     * @return array<int, string> file names relative to the set's url, tried in order
     */
    public function files(array $codepoints): array;
}
