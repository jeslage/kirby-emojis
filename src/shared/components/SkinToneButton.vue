<script setup>
// Independent of frimousse's store, so it can live outside <EmojiPicker.Root>.
import { computed } from "vue";
import { hostUi, t } from "../host.js";
import { SKIN_TONE_MODIFIERS as MODIFIERS, SKIN_TONES as TONES } from "../skin-tones.js";

const props = defineProps({
    modelValue: { type: String, default: "none" },
    size: { type: String, default: "xs" },
});

const emit = defineEmits(["update:modelValue"]);

const Button = hostUi().Button;
const current = computed(() => (TONES.includes(props.modelValue) ? props.modelValue : "none"));
const label = computed(() => `${t("change_skin_tone")} (${t(`skin_tones.${current.value}`)})`);

function next() {
    emit("update:modelValue", TONES[(TONES.indexOf(current.value) + 1) % TONES.length]);
}
</script>

<template>
    <component :is="Button" :size="size" :label="label" @click="next">
        <span class="emojis-skin-tone">✋{{ MODIFIERS[current] }}</span>
    </component>
</template>
