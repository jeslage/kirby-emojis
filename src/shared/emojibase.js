import { hostUrls, siteConfig, storageKey } from "./host.js";

// Keep in sync with Emojibase::CATEGORIES in PHP.
export const CATEGORIES = [
    "smileys-people",
    "animals-nature",
    "food-drink",
    "travel-places",
    "activities",
    "objects",
    "symbols",
    "flags",
];

export function emojibaseUrl(categories = []) {
    const key = categories?.length ? [...categories].sort().join(",") : "all";

    return hostUrls().emojibase(key);
}

const stash = new Map();

// frimousse caches its data in localStorage per locale only, regardless of the URL: stash it per URL
// and restore the new URL's data (or drop it so frimousse fetches it) before a picker mounts.
export function prepareFrimousseCache(locale, url) {
    try {
        const dataKey = `frimousse/data/${locale}`;
        const markerKey = storageKey(`data-url/${locale}`);
        const currentUrl = localStorage.getItem(markerKey);

        if (currentUrl === url) return;

        const cached = localStorage.getItem(dataKey);
        if (currentUrl && cached) stash.set(`${locale}|${currentUrl}`, cached);

        const restored = stash.get(`${locale}|${url}`);
        if (restored) localStorage.setItem(dataKey, restored);
        else localStorage.removeItem(dataKey);

        localStorage.setItem(markerKey, url);
    } catch {
        // Storage unavailable
    }
}

// Same as EmojiSets::EMOJI_PATTERN. Subdivision flags must come before the general case, which would match the 🏴 alone.
export const EMOJI_PATTERN =
    /\p{Regional_Indicator}{2}|[#*0-9]\uFE0F?\u20E3|\u{1F3F4}[\u{E0061}-\u{E007A}]+\u{E007F}|\p{Extended_Pictographic}(?:\uFE0F|\p{Emoji_Modifier})*(?:\u200D\p{Extended_Pictographic}(?:\uFE0F|\p{Emoji_Modifier})*)*/gu;

// Same as EmojiSets::codepoints() in PHP.
export function codepoints(emoji) {
    return Array.from(emoji ?? "", (char) => char.codePointAt(0).toString(16)).join("-");
}

function setsConfig() {
    return siteConfig;
}

export function siteSet() {
    return setsConfig().set || "native";
}

export function emojiImageUrl(emoji, set = siteSet()) {
    return !set || set === "native" ? null : `${setsConfig().imageUrl}/${set}/${codepoints(emoji)}.svg`;
}

// Real category indexes are >= 0
const RECENT_CATEGORY = -1;

// Takes effect when the picker mounts, so on a picker's very first load (nothing cached yet) only from the next opening on.
export function applyRecentToFrimousseCache(locale, items, label) {
    try {
        const key = `frimousse/data/${locale}`;
        const raw = localStorage.getItem(key);
        if (!raw) return;

        const cache = JSON.parse(raw);
        const data = cache?.data;
        if (!Array.isArray(data?.emojis) || !Array.isArray(data?.categories)) return;

        data.emojis = data.emojis.filter((emoji) => emoji.category !== RECENT_CATEGORY);
        data.categories = data.categories.filter((category) => category.index !== RECENT_CATEGORY);

        if (items.length) {
            data.categories.unshift({ index: RECENT_CATEGORY, label });
            data.emojis.unshift(
                // version 0: always supported; no skins: the stored emoji already has its skin tone
                ...items.map((item) => ({
                    emoji: item.emoji,
                    category: RECENT_CATEGORY,
                    label: item.label,
                    version: 0,
                    tags: [],
                })),
            );
        }

        localStorage.setItem(key, JSON.stringify(cache));
    } catch {
        // Storage unavailable or unexpected data
    }
}
