import { locale } from "./host.js";

// Keep in sync with vue-frimousse's EMOJIBASE_LOCALES and Emojibase::LOCALES in PHP.
const EMOJIBASE_LOCALES = [
    "bn",
    "da",
    "de",
    "en-gb",
    "en",
    "es-mx",
    "es",
    "et",
    "fi",
    "fr",
    "hi",
    "hu",
    "it",
    "ja",
    "ko",
    "lt",
    "ms",
    "nb",
    "nl",
    "pl",
    "pt",
    "ru",
    "sv",
    "th",
    "uk",
    "vi",
    "zh-hant",
    "zh",
];

const ZH_HANT = ["zh-tw", "zh-hk", "zh-mo", "zh-hant"];

export function emojibaseLocale(locale) {
    const normalized = String(locale || "en")
        .toLowerCase()
        .replace("_", "-");

    if (ZH_HANT.includes(normalized)) return "zh-hant";
    if (EMOJIBASE_LOCALES.includes(normalized)) return normalized;

    const language = normalized.split("-")[0];
    if (language === "no" || language === "nn") return "nb";

    return EMOJIBASE_LOCALES.includes(language) ? language : "en";
}

export function cpEmojibaseLocale() {
    return emojibaseLocale(locale());
}
