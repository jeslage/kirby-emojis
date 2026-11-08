<script>
// Structure table and block preview of emoji and emojitext fields
import { parseEmojiText, partText } from "../../shared/emoji-text.js";
import EmojiImage from "../../shared/components/EmojiImage.vue";

export default {
    components: { EmojiImage },
    props: {
        value: String,
        column: Object,
        field: Object,
    },
    computed: {
        parts() {
            const text = String(this.value ?? "");
            const short = [...text].length > 50 ? [...text].slice(0, 50).join("") + "…" : text;
            return parseEmojiText(short);
        },
    },
    methods: { partText },
};
</script>

<template>
    <div class="k-emoji-field-preview k-text-field-preview">
        <template v-for="(part, index) in parts" :key="index">
            <template v-if="'text' in part">{{ part.text }}</template>
            <EmojiImage v-else :emoji="partText(part)" />
        </template>
    </div>
</template>

<style>
.k-emoji-field-preview {
    padding: var(--table-cell-padding, 0 var(--spacing-3));
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}
.k-emoji-field-preview .emojis-native {
    font-family: var(--emojis-font);
}
</style>
