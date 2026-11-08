import { vi } from "vitest";

export function mockFetch(routes) {
    const fetch = vi.fn(async (url) => {
        const data = routes[url];
        return data === undefined
            ? { ok: false, status: 404, json: async () => null }
            : { ok: true, status: 200, json: async () => data };
    });
    vi.stubGlobal("fetch", fetch);
    return fetch;
}

export const CUSTOM_URL = "/api/emojis/custom";
export const DATA_BASE = "/media/emojis-data";
export const DATA_URL = `${DATA_BASE}/all/en/data.json`;
export const SHORTCODES_URL = `${DATA_BASE}/shortcodes.json`;

export const CUSTOM_EMOJIS = [
    { shortcode: "approved", label: "Approved", keywords: ["ok", "check"], url: "/media/site/approved.svg" },
    { shortcode: "party-parrot", label: "Party Parrot", keywords: [], url: "/media/site/party-parrot.svg" },
];
