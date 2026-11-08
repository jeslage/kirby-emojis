import { beforeAll, beforeEach, describe, expect, it } from "vitest";
import { loadCustomEmojis } from "../../src/shared/custom-emojis.js";
import { parseEmojiText, partText } from "../../src/shared/emoji-text.js";
import { resetConfig } from "./setup.js";
import { CUSTOM_EMOJIS, CUSTOM_URL, mockFetch } from "./helpers.js";

describe("parseEmojiText", () => {
    beforeAll(async () => {
        resetConfig({ custom: true });
        mockFetch({ [CUSTOM_URL]: CUSTOM_EMOJIS });
        await loadCustomEmojis();
    });

    beforeEach(() => resetConfig({ custom: true }));

    it("splits out known custom emojis; unknown shortcodes stay text", () => {
        expect(parseEmojiText("Done :approved: :unknown: 🎉")).toEqual([
            { text: "Done " },
            { shortcode: "approved", label: "Approved", url: "/media/site/approved.svg" },
            { text: " :unknown: 🎉" },
        ]);
    });

    it("finds a shortcode right after a colon that started an unknown one", () => {
        expect(parseEmojiText(":a:approved:").map(partText)).toEqual([":a", ":approved:"]);
    });

    it("splits out Unicode emojis as images of an image set", () => {
        resetConfig({ custom: true, set: "noto" });

        expect(parseEmojiText("Hi 👍🏽!")).toEqual([
            { text: "Hi " },
            { emoji: "👍🏽", url: "/media/emojis/noto/1f44d-1f3fd.svg" },
            { text: "!" },
        ]);
    });

    it("keeps emojis without an image in the set as text", () => {
        resetConfig({ custom: true, set: "noto" });

        expect(parseEmojiText("🎉🚀", { missing: new Set(["🎉"]) }).map((p) => p.emoji ?? p.text)).toEqual([
            "🎉",
            "🚀",
        ]);
        expect(parseEmojiText("🎉🚀", { missing: new Set(["🎉"]) })[0]).toEqual({ text: "🎉" });
    });

    it("joins back to the original text", () => {
        resetConfig({ custom: true, set: "noto" });
        const text = "Party :party-parrot: 🎉 :nope: 🇩🇪 done";

        expect(parseEmojiText(text).map(partText).join("")).toBe(text);
    });
});
