<script setup>
import { computed } from "vue";
import { emojiImageUrl } from "../emojibase.js";
import { findCustom, isShortcode, useCustomEmojis } from "../custom-emojis.js";

const props = defineProps({
    emoji: { type: String, required: true },
    set: { type: String, default: null },
});

const customEmojis = useCustomEmojis();

const custom = computed(() => (isShortcode(props.emoji) && customEmojis.loaded ? findCustom(props.emoji) : null));
const src = computed(() => (isShortcode(props.emoji) ? null : emojiImageUrl(props.emoji, props.set ?? undefined)));
</script>

<template>
    <img
        v-if="custom"
        :src="custom.url"
        :alt="custom.label"
        :title="emoji"
        class="emojis-image emojis-image--custom"
        draggable="false"
    />
    <img
        v-else-if="src"
        :src="src"
        :alt="emoji"
        class="emojis-image"
        draggable="false"
        loading="lazy"
        decoding="async"
    />
    <span v-else class="emojis-native">{{ emoji }}</span>
</template>
