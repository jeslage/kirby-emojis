import { configureHost, setSiteConfig, siteConfig } from "../shared/host.js";
import { reloadCustomEmojis } from "../shared/custom-emojis.js";
import PanelButton from "./components/PanelButton.vue";

let dataUrl = "";
let configRequest = null;

export function setupHost() {
    configureHost({
        t: (key, data) => window.panel.t(`jeslage.emojis.${key}`, data),
        locale: () => window.panel.translation?.code ?? window.panel.user?.language ?? "en",
        storagePrefix: "kirby-emojis",
        urls: {
            emojibase: (key) => `${dataUrl}/${key}`,
            shortcodes: () => `${dataUrl}/shortcodes.json`,
        },
        loadCustom: () => window.panel.api.get("emojis/custom", {}, { silent: true }),
        ui: { Button: PanelButton },
    });
}

// Site-wide settings from a field's props (no request needed)
export function applyConfig(config) {
    if (!config) return;
    dataUrl = config.dataUrl ?? dataUrl;
    setSiteConfig(config);
}

// Site-wide settings for Writer and Textarea, loaded once
export function ensureConfig() {
    if (siteConfig.loaded) return Promise.resolve(siteConfig);

    configRequest ??= window.panel.api
        .get("emojis/config", {}, { silent: true })
        .then((config) => {
            applyConfig(config);
            return siteConfig;
        })
        .catch(() => {
            configRequest = null;
            return siteConfig;
        });

    return configRequest;
}

// After the settings changed in the Emojis view
export function refreshConfig(config) {
    applyConfig(config);
    reloadCustomEmojis();
}
