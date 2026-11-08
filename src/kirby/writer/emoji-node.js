// Writer node `emoji`: the toolbar button with the picker, custom emojis as inline atoms
// (stored as <span data-emoji="code">:code:</span>, the Writer sanitizer strips <img>),
// :shortcode suggestions and image set emojis.
import { resolveDecorationClasses } from "./decorations.js";
import EmojiPickerPopover from "../components/EmojiPickerPopover.vue";
import { closePopover, isPopoverOpen, openPopover } from "../popover.js";
import { ensureConfig } from "../host.js";
import { t } from "../../shared/host.js";
import { findCustom, isShortcode, loadCustomEmojis } from "../../shared/custom-emojis.js";
import { pickerOptions } from "../../shared/settings.js";
import { createSuggestionsSpec } from "../../shared/prosemirror-suggestions.js";
import { createSetImagesSpec } from "../../shared/prosemirror-set-images.js";

function customNodeView(node) {
    const dom = document.createElement("span");
    dom.className = "k-writer-custom-emoji";
    dom.dataset.emoji = node.attrs.shortcode;
    dom.title = `:${node.attrs.shortcode}:`;
    dom.textContent = `:${node.attrs.shortcode}:`;

    loadCustomEmojis().then(() => {
        const emoji = findCustom(node.attrs.shortcode);
        if (!emoji) return;

        const img = document.createElement("img");
        img.src = emoji.url;
        img.alt = emoji.label;
        img.draggable = false;
        dom.replaceChildren(img);
    });

    return { dom };
}

export default {
    get button() {
        return { icon: "smile", label: t("insert_emoji"), inline: true };
    },

    get schema() {
        return {
            group: "inline",
            inline: true,
            atom: true,
            selectable: true,
            draggable: false,
            attrs: { shortcode: { default: null } },
            parseDOM: [
                {
                    tag: "span[data-emoji]",
                    getAttrs: (dom) => ({ shortcode: dom.getAttribute("data-emoji") }),
                },
            ],
            toDOM: (node) => [
                "span",
                { "data-emoji": node.attrs.shortcode, class: "k-emoji" },
                `:${node.attrs.shortcode}:`,
            ],
            leafText: (node) => `:${node.attrs.shortcode}:`,
        };
    },

    init() {
        ensureConfig();
    },

    commands({ type }) {
        return (event) => {
            const view = this.editor.view;

            if (isPopoverOpen()) return closePopover();

            const anchor = event?.target?.closest?.("button") ?? view.dom;

            openPopover(EmojiPickerPopover, {
                anchor,
                onSelect: (emoji) => {
                    closePopover();
                    const { state, dispatch } = view;
                    const tr = isShortcode(emoji)
                        ? state.tr.replaceSelectionWith(type.create({ shortcode: emoji.slice(1, -1) }), false)
                        : state.tr.insertText(emoji);
                    dispatch(tr.scrollIntoView());
                    view.focus();
                },
            });
        };
    },

    get view() {
        return customNodeView;
    },

    plugins() {
        return [
            createSuggestionsSpec({
                customNode: (state) => state.schema.nodes.emoji,
                createCustom: (state, shortcode) => state.schema.nodes.emoji.create({ shortcode }),
                customEmojis: () => pickerOptions().customEmojis,
            }),
            createSetImagesSpec({ resolve: resolveDecorationClasses }),
        ];
    },
};
