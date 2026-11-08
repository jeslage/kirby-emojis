<script setup>
// Mounted in its own app by the editors, so no host components here.
// mousedown (not click) selects, so the input or editor keeps its focus.
import EmojiImage from "./EmojiImage.vue";

defineProps({
    items: { type: Array, required: true },
    active: { type: Number, default: 0 },
});

const emit = defineEmits(["select", "hover"]);
</script>

<template>
    <ul role="listbox" class="emojis-suggestions">
        <li
            v-for="(item, index) in items"
            :key="item.emoji"
            role="option"
            :aria-selected="index === active"
            class="emojis-suggestions__item"
            @mousedown.prevent="emit('select', item)"
            @mouseenter="emit('hover', index)"
        >
            <span class="emojis-preview"><EmojiImage :emoji="item.emoji" /></span>
            <span class="emojis-suggestions__label" :title="item.label">{{
                item.shortcode ? `:${item.shortcode}:` : item.label
            }}</span>
        </li>
    </ul>
</template>
