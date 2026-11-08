// Shortcode suggestions for ProseMirror editors, as a plain PluginSpec:
// Kirby's Writer takes it as is, TipTap hosts wrap it in `new Plugin(spec)`.
import { createApp, h, reactive } from "vue";
import EmojiSuggestions from "./components/EmojiSuggestions.vue";
import { findTrigger, loadEmojiIndex, searchEmojis } from "./emoji-search.js";
import { isShortcode } from "./custom-emojis.js";
import { siteConfig } from "./host.js";
import { rememberEmoji } from "./recent-emojis.js";

let dropdown = null;
let editors = 0;

// One dropdown for all editors, mounted in its own app (EmojiSuggestions has no host components)
function sharedDropdown() {
    if (dropdown) return dropdown;

    dropdown = reactive({
        visible: false,
        owner: null,
        items: [],
        active: 0,
        top: 0,
        left: 0,
        onSelect: null,
        reposition: null,
    });

    const reposition = () => dropdown.visible && dropdown.reposition?.();
    window.addEventListener("scroll", reposition, true);
    window.addEventListener("resize", reposition);

    const el = document.createElement("div");
    el.className = "emojis-editor-suggestions";
    document.body.appendChild(el);

    createApp({
        render: () =>
            dropdown.visible
                ? h(
                      "div",
                      {
                          style: {
                              position: "fixed",
                              top: `${dropdown.top}px`,
                              left: `${dropdown.left}px`,
                              zIndex: 3000,
                          },
                      },
                      [
                          h(EmojiSuggestions, {
                              items: dropdown.items,
                              active: dropdown.active,
                              onSelect: (item) => dropdown.onSelect?.(item),
                              onHover: (index) => (dropdown.active = index),
                          }),
                      ],
                  )
                : null,
    }).mount(el);

    return dropdown;
}

/**
 * @param {object} options
 * @param {(state) => object|null} options.customNode Node type for custom emojis, if the schema has one
 * @param {(state, shortcode) => object} options.createCustom Creates the node for a custom emoji
 * @param {() => boolean} options.customEmojis Whether custom emojis are suggested
 */
export function createSuggestionsSpec({ customNode, createCustom, customEmojis = () => true }) {
    const list = sharedDropdown();
    // A number, not an object: objects in the reactive list become proxies and would never compare equal
    const owner = ++editors;
    let range = null;
    let request = 0;

    const close = () => {
        request++;
        range = null;
        if (list.owner === owner) list.visible = false;
    };

    const select = (view, item) => {
        if (!range || !item) return;

        const { from, to } = range;
        const { state } = view;
        const tr =
            isShortcode(item.emoji) && customNode(state)
                ? state.tr.replaceWith(from, to, createCustom(state, item.emoji.slice(1, -1)))
                : state.tr.insertText(item.emoji, from, to);

        view.dispatch(tr.scrollIntoView());
        view.focus();
        rememberEmoji(item.emoji, item.label);
        close();
    };

    const position = (view) => {
        if (!range || list.owner !== owner) return;
        const coords = view.coordsAtPos(range.from);
        list.top = coords.bottom + 4;
        list.left = coords.left;
    };

    const update = (view) => {
        const { selection } = view.state;

        if (!view.hasFocus() || !selection.empty || siteConfig.suggestions === false) return close();

        const $from = selection.$from;
        if (!$from.parent.isTextblock) return close();

        const before = $from.parent.textBetween(Math.max(0, $from.parentOffset - 50), $from.parentOffset, null, "￼");
        const trigger = findTrigger(before);
        if (!trigger) return close();

        const from = selection.from - trigger.length;
        const to = selection.from;
        const current = ++request;

        loadEmojiIndex().then(() => {
            if (current !== request) return;

            const items = searchEmojis(trigger.query, { customEmojis: customEmojis() && !!customNode(view.state) });
            if (!items.length) return close();

            range = { from, to };
            Object.assign(list, {
                owner,
                items,
                active: 0,
                visible: true,
                onSelect: (item) => select(view, item),
                reposition: () => position(view),
            });
            position(view);
        });
    };

    // Capture phase on the editor element: the editor's own key handling (Enter = new paragraph)
    // runs before plugin props and would take Enter and Tab
    const handleKey = (view, event) => {
        if (!list.visible || !range || list.owner !== owner) return false;

        const count = list.items.length;
        if (event.key === "ArrowDown") list.active = (list.active + 1) % count;
        else if (event.key === "ArrowUp") list.active = (list.active - 1 + count) % count;
        else if (event.key === "Enter" || event.key === "Tab") select(view, list.items[list.active]);
        else if (event.key === "Escape") close();
        else return false;

        return true;
    };

    return {
        view: (editorView) => {
            const onKeydown = (event) => {
                if (handleKey(editorView, event)) {
                    event.preventDefault();
                    event.stopPropagation();
                }
            };

            editorView.dom.addEventListener("keydown", onKeydown, true);

            return {
                update,
                destroy: () => {
                    editorView.dom.removeEventListener("keydown", onKeydown, true);
                    close();
                },
            };
        },
        props: {
            handleDOMEvents: {
                blur: () => {
                    close();
                    return false;
                },
            },
        },
    };
}
