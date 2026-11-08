import { ref } from "vue";
import { storageKey } from "./host.js";

const MAX = 27; // three rows

// Read lazily: the host sets the storage prefix after this module loads
const storage = () => storageKey("recent");

function read() {
    try {
        const list = JSON.parse(localStorage.getItem(storage()) ?? "[]");
        return Array.isArray(list) ? list.filter((item) => typeof item?.emoji === "string") : [];
    } catch {
        return [];
    }
}

let recent = null;

export function useRecentEmojis() {
    recent ??= ref(read());
    return recent;
}

export function rememberEmoji(emoji, label) {
    const list = useRecentEmojis();
    list.value = [{ emoji, label: label ?? emoji }, ...list.value.filter((item) => item.emoji !== emoji)].slice(0, MAX);

    try {
        localStorage.setItem(storage(), JSON.stringify(list.value));
    } catch {
        // Storage unavailable
    }
}
