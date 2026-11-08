<script setup>
// Picker in a floating panel, used by the fields, Writer and Textarea
import { computed } from "vue";
import EmojiPicker from "../../shared/components/EmojiPicker.vue";
import { pickerOptions } from "../../shared/settings.js";
import FloatingPanel from "./FloatingPanel.vue";

const props = defineProps({
    anchor: { type: Object, required: true },
    // Field overrides (categories, skinTone, skinToneSelector, custom)
    options: { type: Object, default: () => ({}) },
    align: { type: String, default: "start" },
});

const emit = defineEmits(["select", "close"]);
const picker = computed(() => pickerOptions(props.options));
</script>

<template>
    <FloatingPanel :anchor="anchor" :align="align" @close="emit('close')">
        <EmojiPicker
            :categories="picker.categories"
            :skin-tone="picker.skinTone"
            :skin-tone-selector="picker.skinToneSelector"
            :custom-emojis="picker.customEmojis"
            :recent-emojis="picker.recentEmojis"
            @select="emit('select', $event)"
        />
    </FloatingPanel>
</template>
