// The only connection between the shared picker code and the CMS it runs in (Kirby, Statamic).
// The host calls configureHost() once before any component mounts.
import { reactive } from "vue";

const host = {
    // Translates a key without the host's prefix, e.g. "search_tab"
    t: (key) => key,
    // Panel UI language, e.g. "de"
    locale: () => "en",
    // Prefix for localStorage keys
    storagePrefix: "emojis",
    urls: {
        // Emojibase data base URL for the given categories key ("all" or "a,b"); frimousse appends /{locale}/data.json
        emojibase: (key) => `/emojis-data/${key}`,
        shortcodes: () => "/emojis-data/shortcodes.json",
    },
    // Loads the custom emoji list: () => Promise<Array>
    loadCustom: () => Promise.resolve([]),
    // UI components with the shared contract (see README in this folder)
    ui: {},
};

// Site-wide settings: set, imageUrl, custom, categories, skinTone, skinToneSelector, recent, suggestions
export const siteConfig = reactive({
    set: "native",
    imageUrl: "",
    custom: false,
    categories: [],
    skinTone: "none",
    skinToneSelector: true,
    recent: true,
    suggestions: true,
    loaded: false,
});

export function configureHost(options) {
    Object.assign(host, options, { urls: { ...host.urls, ...options.urls }, ui: { ...host.ui, ...options.ui } });
}

export function setSiteConfig(config = {}) {
    for (const key of Object.keys(siteConfig)) {
        if (key in config) siteConfig[key] = config[key];
    }
    siteConfig.loaded = true;
}

export function t(key, replace = {}) {
    let text = host.t(key, replace) ?? key;
    for (const [name, value] of Object.entries(replace)) text = text.replaceAll(`{${name}}`, value);
    return text;
}

export const locale = () => host.locale();
export const storageKey = (name) => `${host.storagePrefix}/${name}`;
export const loadCustom = () => host.loadCustom();
export const hostUrls = () => host.urls;
export const hostUi = () => host.ui;
