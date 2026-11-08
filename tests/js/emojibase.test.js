import { beforeEach, describe, expect, it } from "vitest";
import { EMOJI_PATTERN, codepoints, emojiImageUrl, siteSet } from "../../src/shared/emojibase.js";
import { resetConfig } from "./setup.js";

const matches = (text) => [...text.matchAll(EMOJI_PATTERN)].map((m) => m[0]);

describe("EMOJI_PATTERN", () => {
    it("matches single emojis, sequences, flags and keycaps as one emoji each", () => {
        expect(matches("Hi 🎉 there")).toEqual(["🎉"]);
        expect(matches("👍🏽🧑‍🚀👨‍👩‍👧")).toEqual(["👍🏽", "🧑‍🚀", "👨‍👩‍👧"]);
        expect(matches("🇩🇪🇫🇷")).toEqual(["🇩🇪", "🇫🇷"]);
        expect(matches("1️⃣ #️⃣")).toEqual(["1️⃣", "#️⃣"]);
        expect(matches("❤️ 🏴󠁧󠁢󠁳󠁣󠁴󠁿")).toEqual(["❤️", "🏴󠁧󠁢󠁳󠁣󠁴󠁿"]);
    });

    it("leaves plain text alone", () => {
        expect(matches("Hello world 123 :approved:")).toEqual([]);
    });
});

describe("emoji images", () => {
    beforeEach(() => resetConfig());

    it("builds codepoints like the PHP side", () => {
        expect(codepoints("🎉")).toBe("1f389");
        expect(codepoints("👍🏽")).toBe("1f44d-1f3fd");
        expect(codepoints("❤️")).toBe("2764-fe0f");
    });

    it("has no image URL for the native set", () => {
        expect(siteSet()).toBe("native");
        expect(emojiImageUrl("🎉")).toBeNull();
    });

    it("uses the site-wide image set", () => {
        resetConfig({ set: "noto" });
        expect(emojiImageUrl("🎉")).toBe("/media/emojis/noto/1f389.svg");
    });
});
