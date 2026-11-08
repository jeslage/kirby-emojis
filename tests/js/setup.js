import { afterEach, vi } from "vitest";
import { configureHost, setSiteConfig } from "../../src/shared/host.js";
import { CUSTOM_URL, DATA_BASE, SHORTCODES_URL } from "./helpers.js";

configureHost({
    t: (key) => key,
    locale: () => "en",
    storagePrefix: "kirby-emojis",
    urls: {
        emojibase: (key) => `${DATA_BASE}/${key}`,
        shortcodes: () => SHORTCODES_URL,
    },
    loadCustom: () => fetch(CUSTOM_URL).then((response) => (response.ok ? response.json() : [])),
    ui: { Button: { props: ["label", "active", "size"], template: "<button :aria-label='label'><slot /></button>" } },
});

// Site-wide settings, as the Panel gets them from the plugin
export function resetConfig(config = {}) {
    setSiteConfig({
        set: "native",
        imageUrl: "/media/emojis",
        custom: false,
        categories: [],
        skinTone: "none",
        skinToneSelector: true,
        recent: true,
        suggestions: true,
        ...config,
    });
}

resetConfig();

// jsdom has no layout: ranges have no client rects
Range.prototype.getBoundingClientRect ??= () => new DOMRect(0, 0, 0, 0);

afterEach(() => {
    vi.unstubAllGlobals();
    localStorage.clear();
    sessionStorage.clear();
});
