<script setup>
// Kirby implementation of the shared button contract: label (tooltip + aria-label), active, size, default slot
defineProps({
    label: { type: String, default: null },
    active: { type: Boolean, default: false },
    size: { type: String, default: "sm" },
});

defineEmits(["click"]);
</script>

<template>
    <button
        type="button"
        class="emojis-panel-button"
        :data-active="active || undefined"
        :data-size="size"
        :title="label"
        :aria-label="label"
        @click="$emit('click', $event)"
    >
        <slot />
    </button>
</template>

<style>
.emojis-panel-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--height-sm);
    height: var(--height-sm);
    color: var(--color-text);
    background: transparent;
    border-radius: var(--rounded);
    cursor: pointer;
}
.emojis-panel-button[data-size="xs"] {
    width: var(--height-xs);
    height: var(--height-xs);
}
.emojis-panel-button:hover {
    background: var(--emojis-hover);
}
.emojis-panel-button[data-active] {
    background: var(--color-blue-200);
    color: var(--color-blue-800);
}
.k-panel[data-theme="dark"] .emojis-panel-button[data-active] {
    background: var(--color-blue-800);
    color: var(--color-blue-200);
}
.emojis-panel-button:focus-visible {
    outline: var(--outline);
}
</style>
