<script setup>
// A popover anchored to an element, teleported to the body so dialogs and
// overflow: hidden containers don't clip it. Closes on outside click and Escape.
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";

const props = defineProps({
    anchor: { type: Object, required: true },
    align: { type: String, default: "start" },
});

const emit = defineEmits(["close"]);
const el = ref(null);
const style = ref({ visibility: "hidden" });
const GAP = 6;
const MARGIN = 8;

function position() {
    if (!el.value || !props.anchor?.isConnected) return;

    const anchor = props.anchor.getBoundingClientRect();
    const box = el.value.getBoundingClientRect();
    const vw = document.documentElement.clientWidth;
    const vh = document.documentElement.clientHeight;

    let top = anchor.bottom + GAP;
    if (top + box.height > vh - MARGIN && anchor.top - GAP - box.height >= MARGIN) {
        top = anchor.top - GAP - box.height;
    }

    let left = props.align === "end" ? anchor.right - box.width : anchor.left;
    left = Math.min(Math.max(MARGIN, left), vw - box.width - MARGIN);

    style.value = { top: `${Math.max(MARGIN, top)}px`, left: `${left}px` };
}

function onPointerDown(event) {
    if (el.value?.contains(event.target) || props.anchor?.contains(event.target)) return;
    emit("close");
}

function onKeydown(event) {
    if (event.key === "Escape") {
        event.stopPropagation();
        emit("close");
    }
}

let observer = null;

onMounted(async () => {
    await nextTick();
    position();
    document.addEventListener("pointerdown", onPointerDown, true);
    window.addEventListener("resize", position);
    window.addEventListener("scroll", position, true);
    observer = new ResizeObserver(position);
    observer.observe(el.value);
});

onBeforeUnmount(() => {
    document.removeEventListener("pointerdown", onPointerDown, true);
    window.removeEventListener("resize", position);
    window.removeEventListener("scroll", position, true);
    observer?.disconnect();
});

watch(() => props.anchor, position);
</script>

<template>
    <Teleport to="body">
        <div ref="el" class="emojis-floating" :style="style" @keydown="onKeydown">
            <slot />
        </div>
    </Teleport>
</template>

<style>
.emojis-floating {
    position: fixed;
    z-index: var(--z-dropdown, 2000);
    color: var(--emojis-text);
    background: var(--emojis-back);
    border: 1px solid var(--emojis-border);
    border-radius: var(--rounded-lg);
    box-shadow: var(--shadow-xl);
    overflow: hidden;
}
</style>
