<script setup>
import { t } from "../host.js";
import EmojiImage from "./EmojiImage.vue";
import SkinToneButton from "./SkinToneButton.vue";

defineProps({
    emoji: { type: String, default: null },
    label: { type: String, default: null },
    skinToneSelector: { type: Boolean, default: true },
    skinTone: { type: String, default: "none" },
});

const emit = defineEmits(["update:skinTone"]);
</script>

<template>
    <div class="emojis-picker__footer">
        <template v-if="emoji">
            <span class="emojis-preview emojis-picker__footer-emoji" aria-hidden="true">
                <EmojiImage :emoji="emoji" />
            </span>
            <span class="emojis-picker__footer-label" :title="label">{{ label }}</span>
        </template>
        <span v-else class="emojis-picker__footer-label emojis-muted">{{ t("pick_an_emoji") }}</span>

        <SkinToneButton
            v-if="skinToneSelector"
            class="emojis-picker__skin-tone"
            size="sm"
            :model-value="skinTone"
            @update:model-value="emit('update:skinTone', $event)"
        />
    </div>
</template>
