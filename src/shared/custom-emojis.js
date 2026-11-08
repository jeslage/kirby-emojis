import { reactive } from "vue";
import { loadCustom, siteConfig } from "./host.js";

const state = reactive({ list: [], loaded: false });
let request = null;

export const SHORTCODE = /^:([a-z0-9_+-]+):$/;

export function customEmojisEnabled() {
    return !!siteConfig.custom;
}

export function loadCustomEmojis() {
    if (!customEmojisEnabled()) {
        state.loaded = true;
        return Promise.resolve([]);
    }

    request ??= Promise.resolve()
        .then(() => loadCustom())
        .then((list) => (Array.isArray(list) ? list : []))
        .catch(() => [])
        .then((list) => {
            state.list = list;
            state.loaded = true;
            return list;
        });

    return request;
}

// Drops the loaded list, e.g. after uploading a custom emoji
export function reloadCustomEmojis() {
    request = null;
    return loadCustomEmojis();
}

export function useCustomEmojis() {
    loadCustomEmojis();
    return state;
}

export function isShortcode(value) {
    return typeof value === "string" && SHORTCODE.test(value);
}

export function findCustom(code) {
    const shortcode = String(code ?? "").replace(/^:|:$/g, "");
    return state.list.find((emoji) => emoji.shortcode === shortcode) ?? null;
}

export function searchCustom(query) {
    const q = String(query ?? "")
        .trim()
        .toLowerCase();
    if (!q) return [];

    return state.list.filter((emoji) =>
        [emoji.shortcode, emoji.label, ...(emoji.keywords ?? [])].some((text) =>
            String(text).toLowerCase().includes(q),
        ),
    );
}
