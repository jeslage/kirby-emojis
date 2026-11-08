<script setup>
import EmojiImage from "./EmojiImage.vue";

defineProps({
    items: { type: Array, required: true },
    columns: { type: Number, default: 9 },
});

const emit = defineEmits(["select", "hover"]);
</script>

<template>
    <div
        class="emojis-grid"
        :style="{ gridTemplateColumns: `repeat(${columns}, var(--emojis-cell-size))` }"
        role="grid"
    >
        <button
            v-for="item in items"
            :key="item.emoji"
            type="button"
            role="gridcell"
            frimousse-emoji=""
            :aria-label="item.label"
            :title="item.emoji.startsWith(':') ? item.emoji : item.label"
            @click="emit('select', item)"
            @pointerenter="emit('hover', item)"
            @pointerleave="emit('hover', null)"
            @focus="emit('hover', item)"
            @blur="emit('hover', null)"
        >
            <EmojiImage :emoji="item.emoji" />
        </button>
    </div>
</template>
