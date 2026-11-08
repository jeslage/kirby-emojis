import { siteConfig } from "./host.js";

export function siteSettings() {
    return siteConfig;
}

function showHide(value, fallback) {
    if (value === true || value === "show") return true;
    if (value === false || value === "hide") return false;
    return fallback;
}

// Picker options from a field's config, falling back to the site-wide settings
export function pickerOptions(config = {}) {
    const site = siteSettings();

    return {
        categories: config.categories?.length ? config.categories : (site.categories ?? []),
        skinTone: config.skinTone || site.skinTone || "none",
        skinToneSelector: showHide(config.skinToneSelector, site.skinToneSelector ?? true),
        customEmojis: showHide(config.custom, true) && !!site.custom,
        recentEmojis: site.recent ?? true,
    };
}
