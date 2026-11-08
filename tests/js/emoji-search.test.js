import { beforeAll, describe, expect, it } from "vitest";
import { findTrigger, loadEmojiIndex, searchEmojis } from "../../src/shared/emoji-search.js";
import { loadCustomEmojis } from "../../src/shared/custom-emojis.js";
import { resetConfig } from "./setup.js";
import { CUSTOM_EMOJIS, CUSTOM_URL, DATA_URL, SHORTCODES_URL, mockFetch } from "./helpers.js";

describe("findTrigger", () => {
    it.each([
        [":rock", "rock"],
        ["hi :rock", "rock"],
        ["👍:rock", "rock"],
        ["🧑‍🚀:ro", "ro"],
        [":approved::ro", "ro"],
        ["(:ro", "ro"],
        ["hi :ROCK", "rock"],
    ])("finds the query in %j", (text, query) => {
        expect(findTrigger(text)).toEqual({ query, length: query.length + 1 });
    });

    it.each(["10:30", "a:bc", "Ärger:ro", "https://x", "hi :r", "hi :rock done", ""])(
        "does not trigger in %j",
        (text) => expect(findTrigger(text)).toBeNull(),
    );
});

describe("searchEmojis", () => {
    beforeAll(async () => {
        resetConfig({ custom: true });
        mockFetch({
            [CUSTOM_URL]: CUSTOM_EMOJIS,
            [DATA_URL]: [
                { emoji: "🪨", hexcode: "1FAA8", label: "rock", group: 7, version: 13, tags: ["stone"] },
                { emoji: "🚀", hexcode: "1F680", label: "rocket", group: 5, version: 0.6, tags: ["space"] },
                {
                    emoji: "🧑‍🎤",
                    hexcode: "1F9D1-200D-1F3A4",
                    label: "singer",
                    group: 1,
                    version: 12,
                    tags: ["rock", "star"],
                },
                {
                    emoji: "👍",
                    hexcode: "1F44D",
                    label: "thumbs up",
                    group: 1,
                    version: 0.6,
                    tags: ["yes"],
                    skins: [{ emoji: "👍🏽", hexcode: "1F44D-1F3FD", label: "thumbs up: medium skin tone", tone: 3 }],
                },
                { emoji: "🏽", hexcode: "1F3FD", label: "medium skin tone", group: 2, version: 1 },
                { emoji: "🫨", hexcode: "1FAE8", label: "shaking face", group: 0, version: 15.1, tags: ["shock"] },
            ],
            [SHORTCODES_URL]: {
                "1FAA8": "rock",
                "1F680": "rocket",
                "1F9D1-200D-1F3A4": "singer",
                "1F44D": ["+1", "thumbsup", "yes"],
                "1F44D-1F3FD": ["+1_tone3", "thumbsup_tone3"],
            },
        });
        await Promise.all([loadEmojiIndex(), loadCustomEmojis()]);
    });

    it("ranks shortcode matches before name and tag matches", () => {
        expect(searchEmojis("rock").map((i) => `${i.emoji} ${i.shortcode}`)).toEqual([
            "🪨 rock",
            "🚀 rocket",
            "🧑‍🎤 singer",
        ]);
    });

    it("returns the matching shortcode of emojis with several", () => {
        expect(searchEmojis("thumb")[0]).toEqual({ emoji: "👍", label: "thumbs up", shortcode: "thumbsup" });
    });

    it('lists custom emojis first, as ":shortcode:"', () => {
        expect(searchEmojis("app")[0]).toEqual({ emoji: ":approved:", label: "Approved", shortcode: "approved" });
        expect(searchEmojis("check")[0].emoji).toBe(":approved:");
        expect(searchEmojis("app", { customEmojis: false })).toEqual([]);
    });

    it("applies the remembered skin tone", () => {
        localStorage.setItem("kirby-emojis/skin-tone", "medium");
        expect(searchEmojis("thumb")[0]).toMatchObject({ emoji: "👍🏽", shortcode: "thumbsup_tone3" });
    });

    it("skips skin tone components and emojis newer than the device supports", () => {
        expect(searchEmojis("medium")).toEqual([]);
        expect(searchEmojis("shaking")).toEqual([]);
    });

    it("limits the results", () => {
        expect(searchEmojis("r", { limit: 2 })).toHaveLength(2);
    });
});
