<script setup>
// A contenteditable, as a native <input> can't show images. The value stays a plain string (images as ":shortcode:").
// Only the editable element: the host wraps it in its own input frame (prepend, counter, buttons).
import { nextTick, onBeforeUnmount, onMounted, ref, useTemplateRef, watch } from "vue";
import { useCustomEmojis } from "../custom-emojis.js";
import { parseEmojiText } from "../emoji-text.js";

defineOptions({ inheritAttrs: false });

const props = defineProps({
    modelValue: { type: String, default: null },
    placeholder: { type: String, default: null },
    readOnly: { type: Boolean, default: false },
    id: { type: String, default: null },
    dir: { type: String, default: null },
    focus: { type: Boolean, default: false },
    autoselect: { type: Boolean, default: false },
});

const emit = defineEmits(["update:modelValue", "focus", "blur", "keydown"]);

const root = useTemplateRef("root");
const customEmojis = useCustomEmojis();

// Values this input emitted itself. With a debounced field the model lags behind the typing,
// and re-rendering an older emitted value would undo what was typed since.
const emitted = new Set();
// The selection survives the blur caused by opening the picker
let lastSelection = null;
let composing = false;
const missing = new Set();

function parse(value) {
    return parseEmojiText(value, { missing });
}

function isEmoji(node) {
    return node.nodeName === "IMG" && !!(node.dataset.shortcode || node.dataset.emoji);
}

function emojiText(node) {
    return node.dataset.shortcode ? `:${node.dataset.shortcode}:` : node.dataset.emoji;
}

function serialize(node) {
    let out = "";
    for (const child of node.childNodes) {
        if (child.nodeType === Node.TEXT_NODE) out += child.data.replace(/\n/g, " ");
        else if (isEmoji(child)) out += emojiText(child);
        else if (child.nodeType === Node.ELEMENT_NODE && child.nodeName !== "BR") out += serialize(child);
    }
    return out;
}

function rendered(value) {
    const current = [];
    for (const child of root.value.childNodes) {
        if (child.nodeType === Node.TEXT_NODE) {
            if (child.data === "") continue;
            const prev = current.at(-1);
            if (prev && "text" in prev) prev.text += child.data;
            else current.push({ text: child.data });
        } else if (isEmoji(child)) current.push({ image: emojiText(child) });
        else return false;
    }

    const wanted = parse(value).map((part) =>
        "text" in part ? { text: part.text } : { image: part.emoji ?? `:${part.shortcode}:` },
    );
    return JSON.stringify(current) === JSON.stringify(wanted);
}

function render(value) {
    const nodes = parse(value).map((part) => {
        if ("text" in part) return document.createTextNode(part.text);

        const img = document.createElement("img");
        img.draggable = false;
        img.className = "emojis-text__emoji";

        if (part.emoji) {
            img.src = part.url;
            img.alt = part.emoji;
            img.dataset.emoji = part.emoji;
            img.addEventListener(
                "error",
                () => {
                    missing.add(part.emoji);
                    if (root.value) normalize(serialize(root.value));
                },
                { once: true },
            );
            return img;
        }

        img.src = part.url;
        img.alt = `:${part.shortcode}:`;
        img.title = part.label ? `${part.label} :${part.shortcode}:` : `:${part.shortcode}:`;
        img.dataset.shortcode = part.shortcode;
        return img;
    });

    root.value.replaceChildren(...nodes);
    empty.value = value === "";
}

function offsetOf(container, offset) {
    const range = document.createRange();
    range.selectNodeContents(root.value);
    range.setEnd(container, offset);
    return serialize(range.cloneContents()).length;
}

function positionOf(offset) {
    const el = root.value;
    let rest = offset;

    for (let i = 0; i < el.childNodes.length; i++) {
        const child = el.childNodes[i];
        const length =
            child.nodeType === Node.TEXT_NODE ? child.data.length : serialize({ childNodes: [child] }).length;

        if (child.nodeType === Node.TEXT_NODE && rest <= length) return [child, rest];
        if (child.nodeType !== Node.TEXT_NODE && rest <= 0) return [el, i];
        rest -= length;
        if (rest < 0) return [el, i + 1];
    }

    return [el, el.childNodes.length];
}

function hasFocus() {
    return !!root.value && document.activeElement === root.value;
}

function selection() {
    const sel = window.getSelection();
    if (hasFocus() && sel?.rangeCount && root.value.contains(sel.anchorNode)) {
        const range = sel.getRangeAt(0);
        const start = offsetOf(range.startContainer, range.startOffset);
        const end = offsetOf(range.endContainer, range.endOffset);
        return { start, end };
    }

    return lastSelection;
}

function setSelection(start, end = start) {
    const range = document.createRange();
    range.setStart(...positionOf(start));
    range.setEnd(...positionOf(end));
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(range);
    lastSelection = { start, end };
    keepCaretVisible();
}

function keepCaretVisible() {
    const sel = window.getSelection();
    if (!sel?.rangeCount || !root.value) return;

    const caret = sel.getRangeAt(0).getBoundingClientRect();
    const box = root.value.getBoundingClientRect();
    if (!caret.width && !caret.height && !caret.left) return;

    if (caret.right > box.right - 12) root.value.scrollLeft += caret.right - box.right + 12;
    else if (caret.left < box.left + 12) root.value.scrollLeft -= box.left + 12 - caret.left;
}

function normalize(value) {
    if (rendered(value)) return;

    const sel = hasFocus() ? selection() : null;
    render(value);
    if (sel) setSelection(Math.min(sel.start, value.length), Math.min(sel.end, value.length));
}

function onInput() {
    if (composing) return;

    const value = serialize(root.value);
    normalize(value);
    empty.value = value === "";

    if (emitted.size > 50) emitted.clear();
    emitted.add(value);
    emit("update:modelValue", value);
}

function onCompositionEnd() {
    composing = false;
    onInput();
}

function onBeforeInput(event) {
    if (["insertParagraph", "insertLineBreak"].includes(event.inputType)) event.preventDefault();
}

function onKeydown(event) {
    emit("keydown", event);
    if (event.defaultPrevented) return;
    if (event.key === "Enter") event.preventDefault();
}

function insertPlainText(text) {
    document.execCommand("insertText", false, text.replace(/\r?\n/g, " "));
}

function onPaste(event) {
    event.preventDefault();
    insertPlainText(event.clipboardData?.getData("text/plain") ?? "");
}

function onDrop(event) {
    event.preventDefault();
}

function onCopy(event, cut = false) {
    const sel = window.getSelection();
    if (!sel?.rangeCount) return;

    event.preventDefault();
    event.clipboardData.setData("text/plain", serialize(sel.getRangeAt(0).cloneContents()));
    if (cut && !props.readOnly) document.execCommand("delete");
}

function onSelectionChange() {
    if (hasFocus()) lastSelection = selection();
}

function onFocus(event) {
    emit("focus", event);
    if (props.autoselect) nextTick(() => setSelection(0, serialize(root.value).length));
}

function focus() {
    root.value?.focus();
}

watch(
    () => props.modelValue,
    (value) => {
        value ??= "";
        if (!root.value || emitted.has(value) || value === serialize(root.value)) return;

        emitted.clear();
        normalize(value);
    },
);

watch(
    () => customEmojis.list,
    () => root.value && normalize(serialize(root.value)),
);

onMounted(() => {
    render(props.modelValue ?? "");
    document.addEventListener("selectionchange", onSelectionChange);
    if (props.focus) nextTick(focus);
});

onBeforeUnmount(() => document.removeEventListener("selectionchange", onSelectionChange));

const empty = ref(!props.modelValue);

defineExpose({
    el: root,
    focus,
    selection,
    setSelection,
    text: () => (root.value ? serialize(root.value) : (props.modelValue ?? "")),
});
</script>

<template>
    <div class="emojis-text" :data-empty="empty" :data-readonly="readOnly || undefined">
        <div
            ref="root"
            :id="id"
            :dir="dir"
            role="textbox"
            aria-multiline="false"
            :aria-placeholder="placeholder || undefined"
            :aria-readonly="readOnly || undefined"
            :contenteditable="readOnly ? 'false' : 'true'"
            :tabindex="readOnly ? 0 : undefined"
            spellcheck="true"
            class="emojis-text__input"
            v-bind="$attrs"
            @input="onInput"
            @beforeinput="onBeforeInput"
            @keydown="onKeydown"
            @paste="onPaste"
            @drop="onDrop"
            @copy="onCopy($event)"
            @cut="onCopy($event, true)"
            @focus="onFocus"
            @blur="emit('blur', $event)"
            @compositionstart="composing = true"
            @compositionend="onCompositionEnd"
        />
        <div v-if="empty && placeholder" class="emojis-text__placeholder" aria-hidden="true" v-text="placeholder" />
    </div>
</template>
