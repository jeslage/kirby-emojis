<script>
// Picks a single emoji. Value: the emoji as a string, "" when removed.
import EmojiImage from "../../shared/components/EmojiImage.vue";
import EmojiPickerPopover from "../components/EmojiPickerPopover.vue";
import { applyConfig } from "../host.js";
import { t } from "../../shared/host.js";

export default {
    components: { EmojiImage, EmojiPickerPopover },
    inheritAttrs: false,
    props: {
        autofocus: Boolean,
        disabled: Boolean,
        help: String,
        label: String,
        name: [String, Number],
        required: Boolean,
        translate: Boolean,
        type: String,
        value: String,
        picker: { type: Object, default: () => ({}) },
    },
    emits: ["input"],
    data() {
        return { open: false };
    },
    created() {
        applyConfig(this.picker.site);
    },
    watch: {
        picker(picker) {
            applyConfig(picker.site);
        },
    },
    methods: {
        t,
        focus() {
            this.$refs.trigger?.focus();
        },
        select(emoji) {
            this.open = false;
            this.$emit("input", emoji);
            this.$nextTick(() => this.focus());
        },
        remove() {
            this.$emit("input", "");
        },
    },
};
</script>

<template>
    <k-field v-bind="$props" :class="['k-emoji-field', $attrs.class]" :style="$attrs.style" :input="`${name}-trigger`">
        <div v-if="disabled" class="k-emoji-field-readonly">
            <EmojiImage v-if="value" :emoji="value" />
            <template v-else>–</template>
        </div>
        <div v-else class="k-emoji-field-input">
            <!-- One button for both states, so the popover stays anchored to it -->
            <button
                :id="`${name}-trigger`"
                ref="trigger"
                type="button"
                class="k-emoji-field-trigger"
                :data-empty="!value || undefined"
                :aria-expanded="open"
                :aria-label="value ? t('change_emoji') : undefined"
                :title="value ? t('change_emoji') : undefined"
                :autofocus="autofocus"
                @click="open = !open"
            >
                <span v-if="value" class="k-emoji-field-value"><EmojiImage :emoji="value" /></span>
                <template v-else>
                    <k-icon type="smile" />
                    <span>{{ t("select_emoji") }}</span>
                </template>
            </button>
            <k-button
                v-if="value"
                icon="cancel-small"
                size="sm"
                :title="t('remove_emoji')"
                class="k-emoji-field-remove"
                @click="remove"
            />
            <EmojiPickerPopover
                v-if="open"
                :anchor="$refs.trigger"
                :options="picker.field"
                @select="select"
                @close="open = false"
            />
        </div>
    </k-field>
</template>

<style>
.k-emoji-field-input {
    display: flex;
    align-items: center;
    gap: var(--spacing-1);
}
.k-emoji-field-trigger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-2);
    min-width: var(--input-height);
    height: var(--input-height);
    padding: 0 var(--spacing-2);
    font-size: var(--text-sm);
    color: var(--input-color-text);
    background: var(--input-color-back);
    border: 1px solid var(--input-color-border);
    border-radius: var(--input-rounded);
    box-shadow: var(--shadow-sm);
    cursor: pointer;
}
.k-emoji-field-trigger[data-empty] {
    padding: 0 var(--spacing-3);
}
.k-emoji-field-trigger:focus-visible {
    outline: var(--outline);
}
.k-emoji-field-value,
.k-emoji-field-readonly {
    font-family: var(--emojis-font);
    font-size: var(--text-xl);
    line-height: 1;
}
</style>
