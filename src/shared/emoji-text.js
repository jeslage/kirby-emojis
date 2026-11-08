import { findCustom } from "./custom-emojis.js";
import { EMOJI_PATTERN, emojiImageUrl } from "./emojibase.js";

const SHORTCODE = /:([a-z0-9_+-]+):/gi;

function parseCustom(value) {
    const parts = [];
    let last = 0;
    let match;
    SHORTCODE.lastIndex = 0;

    while ((match = SHORTCODE.exec(value))) {
        const custom = findCustom(match[1]);
        if (!custom) {
            // ":a:approved:": the closing colon may start the next shortcode
            SHORTCODE.lastIndex = match.index + 1;
            continue;
        }
        if (match.index > last) parts.push({ text: value.slice(last, match.index) });
        parts.push({ shortcode: custom.shortcode, url: custom.url, label: custom.label });
        last = match.index + match[0].length;
    }
    if (last < value.length) parts.push({ text: value.slice(last) });

    return parts;
}

function parseUnicode(text, missing) {
    if (!emojiImageUrl("😀")) return [{ text }];

    const parts = [];
    let last = 0;
    for (const match of text.matchAll(EMOJI_PATTERN)) {
        if (missing?.has(match[0])) continue;
        if (match.index > last) parts.push({ text: text.slice(last, match.index) });
        parts.push({ emoji: match[0], url: emojiImageUrl(match[0]) });
        last = match.index + match[0].length;
    }
    if (last < text.length) parts.push({ text: text.slice(last) });

    return parts;
}

export function parseEmojiText(value, { missing = null } = {}) {
    return parseCustom(value ?? "").flatMap((part) => ("text" in part ? parseUnicode(part.text, missing) : [part]));
}

export function partText(part) {
    return part.text ?? part.emoji ?? `:${part.shortcode}:`;
}
