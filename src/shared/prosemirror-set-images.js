// Image set emojis in ProseMirror editors: decorations put the image behind a transparent glyph,
// so caret, selection, copy and the saved value keep the real characters.
// Returns a plain PluginSpec. The host passes its Decoration classes, or a resolver that
// gets them from the editor view once it exists.
import { EMOJI_PATTERN, emojiImageUrl } from "./emojibase.js";

const images = new Map(); // emoji → "loading" | "loaded" | "missing"
const views = new Set();
const META = "emojis-set-images";

function imageState(emoji, url, refresh) {
    if (!images.has(emoji)) {
        images.set(emoji, "loading");
        const img = new Image();
        img.onload = () => {
            images.set(emoji, "loaded");
            refresh();
        };
        img.onerror = () => images.set(emoji, "missing");
        img.src = url;
    }
    return images.get(emoji);
}

let pending = false;

function refresh() {
    if (pending) return;
    pending = true;
    requestAnimationFrame(() => {
        pending = false;
        views.forEach((view) => !view.isDestroyed && view.dispatch(view.state.tr.setMeta(META, true)));
    });
}

/**
 * @param {object} options
 * @param {{ Decoration, DecorationSet }} [options.classes]
 * @param {(view) => ({ Decoration, DecorationSet } | null)} [options.resolve]
 */
export function createSetImagesSpec({ classes = null, resolve = null } = {}) {
    const decorate = (doc) => {
        if (!classes) return null;

        const { Decoration, DecorationSet } = classes;
        if (!emojiImageUrl("😀")) return DecorationSet.empty; // native set

        const decorations = [];
        doc.descendants((node, pos) => {
            if (!node.isText) return;

            for (const match of node.text.matchAll(EMOJI_PATTERN)) {
                const url = emojiImageUrl(match[0]);
                if (imageState(match[0], url, refresh) !== "loaded") continue;

                const from = pos + match.index;
                decorations.push(
                    Decoration.inline(from, from + match[0].length, {
                        class: "emojis-editor-image",
                        style: `background-image: url("${url}")`,
                    }),
                );
            }
        });

        return DecorationSet.create(doc, decorations);
    };

    return {
        state: {
            init: (_, state) => decorate(state.doc),
            apply: (tr, old) => (tr.docChanged || tr.getMeta(META) ? decorate(tr.doc) : old),
        },
        props: {
            // `this` is the plugin
            decorations(state) {
                return this.getState(state);
            },
        },
        view: (view) => {
            views.add(view);

            if (!classes && resolve) {
                classes = resolve(view);
                if (classes) refresh();
            }

            return { destroy: () => views.delete(view) };
        },
    };
}
