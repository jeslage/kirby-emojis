// Shortcode suggestions (":rock") for plain inputs: the Emoji Text input and textareas.
// The target describes the input: { el, text(), selection(), insert(start, end, text), anchorRect() }
import { reactive } from "vue";
import { findTrigger, loadEmojiIndex, searchEmojis } from "./emoji-search.js";
import { rememberEmoji } from "./recent-emojis.js";
import { siteConfig } from "./host.js";

export function useInputSuggestions({ target, enabled = () => true, customEmojis = () => true }) {
    const state = reactive({ items: [], active: 0, start: 0, end: 0, top: 0, left: 0 });
    let request = 0;
    let listened = null;

    function position() {
        const rect = target()?.anchorRect?.() ?? target()?.el?.getBoundingClientRect();
        if (!rect) return;
        state.top = rect.bottom + 4;
        state.left = rect.left;
    }

    function watchPosition(on) {
        const method = on ? "addEventListener" : "removeEventListener";
        window[method]("scroll", position, true);
        window[method]("resize", position);
    }

    function close() {
        request++;
        state.items = [];
        watchPosition(false);
    }

    async function update() {
        const input = target();
        const selection = input?.selection();

        if (!selection || !enabled() || siteConfig.suggestions === false || selection.start !== selection.end) {
            return close();
        }

        const cursor = selection.start ?? 0;
        const trigger = findTrigger(input.text().slice(0, cursor));
        if (!trigger) return close();

        const current = ++request;
        await loadEmojiIndex();
        if (current !== request) return;

        const items = searchEmojis(trigger.query, { customEmojis: customEmojis() });
        if (!items.length) return close();

        Object.assign(state, { items, active: 0, start: cursor - trigger.length, end: cursor });
        position();
        watchPosition(true);
    }

    function pick(item) {
        target()?.insert(state.start, state.end, item.emoji);
        rememberEmoji(item.emoji, item.label);
        close();
    }

    function onKeydown(event) {
        const count = state.items.length;
        if (!count) return;

        if (event.key === "ArrowDown") state.active = (state.active + 1) % count;
        else if (event.key === "ArrowUp") state.active = (state.active - 1 + count) % count;
        else if (event.key === "Enter" || event.key === "Tab") pick(state.items[state.active]);
        else if (event.key === "Escape") {
            close();
            event.stopPropagation(); // don't close a surrounding drawer or dialog
        } else return;

        event.preventDefault();
    }

    function onKeyup(event) {
        if (["ArrowLeft", "ArrowRight", "Home", "End"].includes(event.key)) update();
    }

    const listeners = { input: update, click: update, keydown: onKeydown, keyup: onKeyup, blur: close };

    function listen(el) {
        unlisten();
        listened = el ?? null;
        // capture: runs before the input's own keydown handling (e.g. Enter)
        Object.entries(listeners).forEach(([event, fn]) => listened?.addEventListener(event, fn, event === "keydown"));
    }

    function unlisten() {
        Object.entries(listeners).forEach(([event, fn]) =>
            listened?.removeEventListener(event, fn, event === "keydown"),
        );
        listened = null;
        watchPosition(false);
    }

    return { state, update, close, pick, listen, unlisten };
}
