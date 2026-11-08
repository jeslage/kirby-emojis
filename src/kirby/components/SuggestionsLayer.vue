<script setup>
// Suggestions list teleported to <body>, so following fields can't cover it
import EmojiSuggestions from "../../shared/components/EmojiSuggestions.vue";

defineProps({ state: { type: Object, required: true } });
const emit = defineEmits(["pick"]);
</script>

<template>
    <Teleport to="body">
        <EmojiSuggestions
            v-if="state.items.length"
            class="emojis-floating-suggestions"
            :style="{ top: `${state.top}px`, left: `${state.left}px` }"
            :items="state.items"
            :active="state.active"
            @select="emit('pick', $event)"
            @hover="state.active = $event"
        />
    </Teleport>
</template>

<style>
.emojis-floating-suggestions {
    position: fixed;
    z-index: var(--z-dropdown, 2000);
}
</style>
