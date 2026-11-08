import { afterEach, beforeAll, beforeEach, describe, expect, it, vi } from "vitest";
import { mount } from "@vue/test-utils";
import { nextTick } from "vue";
import EmojiTextEditable from "../../src/shared/components/EmojiTextEditable.vue";
import { loadCustomEmojis } from "../../src/shared/custom-emojis.js";
import { resetConfig } from "./setup.js";
import { CUSTOM_EMOJIS, CUSTOM_URL, mockFetch } from "./helpers.js";

let wrapper;

function mountInput(props = {}) {
    wrapper = mount(EmojiTextEditable, { props, attachTo: document.body });
    return wrapper;
}

const editable = () => wrapper.find("[role=textbox]").element;
const images = () => [...editable().querySelectorAll("img")].map((img) => img.dataset.shortcode ?? img.dataset.emoji);

function placeCaret(node, offset) {
    const range = document.createRange();
    range.setStart(node, offset);
    range.collapse(true);
    getSelection().removeAllRanges();
    getSelection().addRange(range);
}

async function type(text) {
    const range = getSelection().getRangeAt(0);
    let node = range.startContainer;
    let offset = range.startOffset;

    if (node.nodeType !== Node.TEXT_NODE) {
        const textNode = document.createTextNode("");
        node.insertBefore(textNode, node.childNodes[offset] ?? null);
        node = textNode;
        offset = 0;
    }

    node.insertData(offset, text);
    placeCaret(node, offset + text.length);
    editable().dispatchEvent(new InputEvent("input", { bubbles: true, inputType: "insertText", data: text }));
    await nextTick();
}

const lastEmitted = () => wrapper.emitted("update:modelValue")?.at(-1)?.[0];

describe("EmojiTextEditable", () => {
    beforeAll(async () => {
        resetConfig({ custom: true });
        mockFetch({ [CUSTOM_URL]: CUSTOM_EMOJIS });
        await loadCustomEmojis();
    });

    beforeEach(() => resetConfig({ custom: true }));

    afterEach(() => wrapper?.unmount());

    it("shows custom emojis as images and keeps the value as text", () => {
        mountInput({ modelValue: "Done :approved: 🎉" });

        expect(images()).toEqual(["approved"]);
        expect(editable().textContent).toBe("Done  🎉");
        expect(wrapper.vm.text()).toBe("Done :approved: 🎉");
    });

    it("shows Unicode emojis in the site-wide image set", () => {
        resetConfig({ custom: true, set: "noto" });
        mountInput({ modelValue: "Hi 🎉 :approved:" });

        expect(images()).toEqual(["🎉", "approved"]);
        expect(editable().querySelector("img").getAttribute("src")).toBe("/media/emojis/noto/1f389.svg");
        expect(wrapper.vm.text()).toBe("Hi 🎉 :approved:");
    });

    it("maps caret positions to offsets in the value, across images", () => {
        mountInput({ modelValue: "ab:approved:cd" });
        editable().focus();

        wrapper.vm.setSelection(12);
        expect(wrapper.vm.selection()).toEqual({ start: 12, end: 12 });

        const range = getSelection().getRangeAt(0);
        expect(range.startContainer.textContent).toBe("cd");
        expect(range.startOffset).toBe(0);

        wrapper.vm.setSelection(2, 12);
        expect(wrapper.vm.selection()).toEqual({ start: 2, end: 12 });
    });

    it("keeps the last selection while the editor does not have focus", () => {
        mountInput({ modelValue: "hello" });
        editable().focus();
        wrapper.vm.setSelection(3);
        editable().blur();

        expect(wrapper.vm.selection()).toEqual({ start: 3, end: 3 });
    });

    it("emits typed text and turns a completed shortcode into an image", async () => {
        mountInput({ modelValue: "Hi " });
        editable().focus();
        placeCaret(editable().firstChild, 3);

        await type(":approved");
        expect(lastEmitted()).toBe("Hi :approved");
        expect(images()).toEqual([]);

        await type(":");
        expect(lastEmitted()).toBe("Hi :approved:");
        expect(images()).toEqual(["approved"]);
        expect(wrapper.vm.selection()).toEqual({ start: 13, end: 13 });
    });

    it("does not undo typing when a debounced model lags behind", async () => {
        mountInput({ modelValue: "a" });
        editable().focus();
        placeCaret(editable().firstChild, 1);

        await type("b");
        await type("c");
        await wrapper.setProps({ modelValue: "ab" });

        expect(wrapper.vm.text()).toBe("abc");
    });

    it("re-renders when the value changes from outside", async () => {
        mountInput({ modelValue: "old" });
        await wrapper.setProps({ modelValue: "new :approved:" });

        expect(wrapper.vm.text()).toBe("new :approved:");
        expect(images()).toEqual(["approved"]);
    });

    it("stays on one line", async () => {
        mountInput({ modelValue: "x" });
        const enter = new KeyboardEvent("keydown", { key: "Enter", cancelable: true, bubbles: true });
        editable().dispatchEvent(enter);

        expect(enter.defaultPrevented).toBe(true);
    });

    it("pastes plain text without line breaks", async () => {
        document.execCommand = vi.fn();
        mountInput({ modelValue: "" });

        const paste = new Event("paste", { cancelable: true, bubbles: true });
        paste.clipboardData = { getData: () => "one\ntwo :approved:" };
        editable().dispatchEvent(paste);

        expect(paste.defaultPrevented).toBe(true);
        expect(document.execCommand).toHaveBeenCalledWith("insertText", false, "one two :approved:");
    });

    it("copies images as their shortcode or emoji", () => {
        resetConfig({ custom: true, set: "noto" });
        mountInput({ modelValue: "a :approved: 🎉" });

        const range = document.createRange();
        range.selectNodeContents(editable());
        getSelection().removeAllRanges();
        getSelection().addRange(range);

        const data = {};
        const copy = new Event("copy", { cancelable: true, bubbles: true });
        copy.clipboardData = { setData: (type, value) => (data[type] = value) };
        editable().dispatchEvent(copy);

        expect(data["text/plain"]).toBe("a :approved: 🎉");
    });

    it("shows the placeholder only while empty", async () => {
        mountInput({ modelValue: "", placeholder: "Mood…" });
        expect(wrapper.text()).toContain("Mood…");

        await wrapper.setProps({ modelValue: "happy" });
        await nextTick();
        expect(wrapper.text()).not.toContain("Mood…");
    });

    it("is not editable when read only", () => {
        mountInput({ modelValue: "x", readOnly: true });

        expect(editable().getAttribute("contenteditable")).toBe("false");
        expect(editable().getAttribute("aria-readonly")).toBe("true");
    });
});
