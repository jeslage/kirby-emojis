<script>
// The core text field with an emoji picker and :shortcode suggestions.
// The input is a contenteditable (shared EmojiTextEditable), as a native <input> can't show images.
import EmojiTextEditable from "../../shared/components/EmojiTextEditable.vue";
import EmojiPickerPopover from "../components/EmojiPickerPopover.vue";
import SuggestionsLayer from "../components/SuggestionsLayer.vue";
import { useInputSuggestions } from "../../shared/input-suggestions.js";
import { pickerOptions } from "../../shared/settings.js";
import { t } from "../../shared/host.js";
import { applyConfig } from "../host.js";

export default {
    components: { EmojiTextEditable, EmojiPickerPopover, SuggestionsLayer },
    inheritAttrs: false,
    props: {
        after: String,
        autofocus: Boolean,
        before: String,
        counter: { type: Boolean, default: true },
        disabled: Boolean,
        font: String,
        help: String,
        icon: String,
        label: String,
        maxlength: Number,
        minlength: Number,
        name: [String, Number],
        placeholder: String,
        required: Boolean,
        spellcheck: { type: Boolean, default: true },
        translate: Boolean,
        type: String,
        value: String,
        picker: { type: Object, default: () => ({}) },
    },
    emits: ["input"],
    data() {
        return {
            open: false,
            // Until the input has been focused, its caret position is meaningless, so emojis are appended
            hasCaret: false,
            suggestions: null,
        };
    },
    computed: {
        id() {
            return `${this.name}-emojitext`;
        },
        counterOptions() {
            if (this.counter === false || this.disabled || !this.value) return false;
            return { count: [...this.value].length, min: this.minlength, max: this.maxlength };
        },
    },
    created() {
        applyConfig(this.picker.site);

        this.suggestions = useInputSuggestions({
            target: () => this.target(),
            enabled: () => !this.disabled,
            customEmojis: () => pickerOptions(this.picker.field).customEmojis,
        });
    },
    mounted() {
        this.suggestions.listen(this.$refs.input?.el);
    },
    beforeUnmount() {
        this.suggestions.unlisten();
    },
    watch: {
        picker(picker) {
            applyConfig(picker.site);
        },
    },
    methods: {
        t,
        focus() {
            this.$refs.input?.focus();
        },
        target() {
            const editor = this.$refs.input;
            if (!editor?.el) return null;

            return {
                el: editor.el,
                text: () => editor.text(),
                selection: () => editor.selection(),
                anchorRect: () => this.$refs.frame?.$el?.getBoundingClientRect(),
                insert: (start, end, text) => this.replace(start, end, text),
            };
        },
        replace(start, end, text) {
            const editor = this.$refs.input;
            const value = editor?.text() ?? this.value ?? "";

            this.$emit("input", value.slice(0, start) + text + value.slice(end));

            this.$nextTick(() => {
                editor?.focus();
                editor?.setSelection(start + text.length);
            });
        },
        insert(emoji) {
            this.open = false;

            const editor = this.$refs.input;
            const value = editor?.text() ?? this.value ?? "";
            const selection = this.hasCaret ? editor?.selection() : null;
            const start = Math.min(selection?.start ?? value.length, value.length);
            const end = Math.min(selection?.end ?? start, value.length);

            this.replace(start, end, emoji);
        },
        onFocus() {
            this.hasCaret = true;
        },
    },
};
</script>

<template>
    <k-field
        v-bind="$props"
        :class="['k-emojitext-field', $attrs.class]"
        :style="$attrs.style"
        :counter="counterOptions"
        :input="id"
    >
        <k-input
            ref="frame"
            :after="after"
            :before="before"
            :disabled="disabled"
            :data-font="font"
            type="emojitext"
            class="k-emojitext-input"
        >
            <EmojiTextEditable
                ref="input"
                :id="id"
                :model-value="value"
                :placeholder="placeholder"
                :read-only="disabled"
                :focus="autofocus"
                :spellcheck="spellcheck"
                @update:model-value="$emit('input', $event)"
                @focus="onFocus"
            />
            <template v-if="!disabled" #icon>
                <button
                    ref="trigger"
                    type="button"
                    class="k-emojitext-trigger"
                    :title="t('insert_emoji')"
                    :aria-label="t('insert_emoji')"
                    :aria-expanded="open"
                    @click.stop="open = !open"
                >
                    <k-icon type="smile" />
                </button>
            </template>
        </k-input>
        <EmojiPickerPopover
            v-if="open"
            :anchor="$refs.trigger"
            :options="picker.field"
            align="end"
            @select="insert"
            @close="open = false"
        />
        <SuggestionsLayer :state="suggestions.state" @pick="suggestions.pick" />
    </k-field>
</template>

<style>
.k-emojitext-input .k-input-element {
    min-width: 0;
}
.k-emojitext-input .emojis-text__input {
    height: var(--input-height);
    padding: var(--input-padding);
    line-height: var(--input-height);
    padding-block: 0;
    color: var(--input-color-text);
}
.k-emojitext-input .emojis-text__placeholder {
    padding: var(--input-padding);
    padding-block: 0;
    color: var(--input-color-placeholder);
}
.k-emojitext-input[data-font="monospace"] .emojis-text__input {
    font-family: var(--font-mono);
}
.k-emojitext-input .k-input-icon {
    padding: 0;
}
.k-emojitext-trigger {
    display: flex;
    align-items: center;
    justify-content: center;
    width: var(--input-height);
    height: var(--input-height);
    color: var(--input-color-icon);
    cursor: pointer;
}
.k-emojitext-trigger:hover {
    color: var(--color-text);
}
.k-emojitext-trigger:focus-visible {
    outline: var(--outline);
    outline-offset: -2px;
}
</style>
