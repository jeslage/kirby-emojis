// :shortcode suggestions in Textarea fields that have the `emoji` toolbar button.
// Attached when such a textarea gets focus, as plugins can't extend the textarea component itself.
import { createApp, h } from "vue";
import SuggestionsLayer from "../components/SuggestionsLayer.vue";
import { useInputSuggestions } from "../../shared/input-suggestions.js";
import { pickerOptions } from "../../shared/settings.js";
import { ensureConfig } from "../host.js";
import { t } from "../../shared/host.js";

const attached = new WeakSet();

// Caret coordinates in a textarea, via a mirror element with the same text layout
function caretRect(textarea, index) {
    const style = getComputedStyle(textarea);
    const mirror = document.createElement("div");

    for (const prop of [
        "boxSizing",
        "width",
        "paddingTop",
        "paddingRight",
        "paddingBottom",
        "paddingLeft",
        "borderTopWidth",
        "borderRightWidth",
        "borderBottomWidth",
        "borderLeftWidth",
        "fontFamily",
        "fontSize",
        "fontWeight",
        "fontStyle",
        "letterSpacing",
        "lineHeight",
        "textTransform",
        "wordSpacing",
        "tabSize",
    ]) {
        mirror.style[prop] = style[prop];
    }

    Object.assign(mirror.style, {
        position: "absolute",
        visibility: "hidden",
        top: "0",
        left: "-9999px",
        whiteSpace: "pre-wrap",
        overflowWrap: "break-word",
    });

    mirror.textContent = textarea.value.slice(0, index);
    const marker = document.createElement("span");
    marker.textContent = "​";
    mirror.append(marker);
    document.body.append(mirror);

    const box = textarea.getBoundingClientRect();
    const top = box.top + marker.offsetTop - textarea.scrollTop;
    const left = box.left + marker.offsetLeft - textarea.scrollLeft;
    const height = parseFloat(style.lineHeight) || marker.offsetHeight;
    mirror.remove();

    return { top, left, bottom: top + height };
}

// Toolbar buttons can't carry custom attributes, so the button is found by its label
function hasEmojiButton(textarea) {
    const label = t("insert_emoji");
    const buttons = textarea.closest(".k-textarea-input")?.querySelectorAll(".k-toolbar button") ?? [];
    return [...buttons].some((button) => button.getAttribute("aria-label") === label || button.title === label);
}

function attach(textarea) {
    attached.add(textarea);
    ensureConfig();

    const suggestions = useInputSuggestions({
        target: () => ({
            el: textarea,
            text: () => textarea.value,
            selection: () => ({ start: textarea.selectionStart, end: textarea.selectionEnd }),
            anchorRect: () => caretRect(textarea, textarea.selectionStart),
            insert: (start, end, text) => {
                textarea.focus();
                textarea.setRangeText(text, start, end, "end");
                textarea.dispatchEvent(new Event("input", { bubbles: true }));
            },
        }),
        enabled: () => !textarea.disabled && !textarea.readOnly,
        customEmojis: () => pickerOptions().customEmojis,
    });

    const host = document.createElement("div");
    document.body.append(host);
    const app = createApp({
        render: () => h(SuggestionsLayer, { state: suggestions.state, onPick: suggestions.pick }),
    });
    app.mount(host);

    suggestions.listen(textarea);

    // Clean up once the textarea leaves the document (navigation, closed drawer)
    const observer = new MutationObserver(() => {
        if (textarea.isConnected) return;
        observer.disconnect();
        suggestions.unlisten();
        app.unmount();
        host.remove();
        attached.delete(textarea);
    });
    observer.observe(document.body, { childList: true, subtree: true });

    // The focusing input already happened; check right away
    suggestions.update();
}

export function installTextareaSuggestions() {
    document.addEventListener("focusin", (event) => {
        const textarea = event.target;
        if (textarea?.tagName !== "TEXTAREA" || attached.has(textarea)) return;
        if (hasEmojiButton(textarea)) attach(textarea);
    });
}
