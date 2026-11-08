// The Writer's image set decorations use a port of prosemirror-view's Decoration together with
// the editor's own DecorationSet (Kirby doesn't expose prosemirror-view). Tested against the real one.
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { EditorState, Plugin } from "prosemirror-state";
import { EditorView } from "prosemirror-view";
import { schema } from "prosemirror-schema-basic";
import { resolveDecorationClasses } from "../../src/kirby/writer/decorations.js";
import { createSetImagesSpec } from "../../src/shared/prosemirror-set-images.js";
import { resetConfig } from "./setup.js";

let view;

function editor(text) {
    const doc = schema.node("doc", null, [
        schema.node("paragraph", null, [schema.text(text)]),
        schema.node("blockquote", null, [schema.node("paragraph", null, [schema.text("Nested 🎉")])]),
    ]);
    const state = EditorState.create({
        doc,
        plugins: [new Plugin(createSetImagesSpec({ resolve: resolveDecorationClasses }))],
    });
    view = new EditorView(document.createElement("div"), { state });
    return view;
}

// Images "load" right away (stubbed per test, setup.js unstubs globals after each)
beforeEach(() => {
    vi.stubGlobal(
        "Image",
        class {
            set src(value) {
                queueMicrotask(() => this.onload?.());
            }
        },
    );
    vi.stubGlobal("requestAnimationFrame", (fn) => setTimeout(fn, 0));
});

afterEach(() => view?.destroy());

const tick = () => new Promise((resolve) => setTimeout(resolve, 5));

describe("writer image set decorations", () => {
    it("finds the editor's DecorationSet", () => {
        resetConfig({ set: "noto" });
        const classes = resolveDecorationClasses(editor("Hi"));
        expect(classes.DecorationSet.empty).toBeInstanceOf(classes.DecorationSet);
    });

    it("decorates emojis with set images, also in nested nodes, and keeps them while editing", async () => {
        resetConfig({ set: "noto" });
        editor("Hi 😀 and 👋🏾!");
        await tick();
        await tick();

        const images = [...view.dom.querySelectorAll(".emojis-editor-image")];
        expect(images.map((el) => el.textContent)).toEqual(["😀", "👋🏾", "🎉"]);
        expect(images[0].getAttribute("style")).toContain("/media/emojis/noto/1f600.svg");

        view.dispatch(view.state.tr.insertText("Yo ", 1));
        await tick();
        expect(view.dom.querySelectorAll(".emojis-editor-image")).toHaveLength(3);
        expect(view.state.doc.textContent).toBe("Yo Hi 😀 and 👋🏾!Nested 🎉");
    });

    it("adds nothing for native emojis", async () => {
        resetConfig({ set: "native" });
        editor("Hi 😀");
        await tick();
        expect(view.dom.querySelectorAll(".emojis-editor-image")).toHaveLength(0);
    });
});
