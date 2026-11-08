<script setup>
import { computed, onMounted, ref, useTemplateRef, watch } from "vue";
import EmojiPicker from "vue-frimousse";
import { Search, Sparkles } from "@lucide/vue";
import { CATEGORY_ICONS } from "../category-icons.js";
import { cpEmojibaseLocale, emojibaseLocale } from "../locale.js";
import { CATEGORIES, applyRecentToFrimousseCache, emojibaseUrl, prepareFrimousseCache } from "../emojibase.js";
import EmojiImage from "./EmojiImage.vue";
import PickerFooter from "./PickerFooter.vue";
import EmojiGrid from "./EmojiGrid.vue";
import { findCustom, isShortcode, searchCustom, useCustomEmojis } from "../custom-emojis.js";
import { rememberEmoji, useRecentEmojis } from "../recent-emojis.js";
import { SKIN_TONES } from "../skin-tones.js";
import { hostUi, storageKey, t } from "../host.js";

const STORAGE_KEY = storageKey("skin-tone");
const Button = hostUi().Button;

const props = defineProps({
    locale: { type: String, default: null },
    columns: { type: Number, default: 9 },
    skinTone: { type: String, default: "none" },
    skinToneSelector: { type: Boolean, default: true },
    categories: { type: Array, default: () => [] },
    customEmojis: { type: Boolean, default: true },
    recentEmojis: { type: Boolean, default: true },
});

const emit = defineEmits(["select"]);

function rememberedSkinTone() {
    try {
        const tone = localStorage.getItem(STORAGE_KEY);
        return SKIN_TONES.includes(tone) ? tone : null;
    } catch {
        return null;
    }
}

const chosenSkinTone = ref(props.skinToneSelector ? rememberedSkinTone() : null);
const pickerSkinTone = computed(
    () => chosenSkinTone.value ?? (SKIN_TONES.includes(props.skinTone) ? props.skinTone : "none"),
);

function onSkinToneChange(tone) {
    chosenSkinTone.value = tone;
    try {
        localStorage.setItem(STORAGE_KEY, tone);
    } catch {
        // Storage unavailable
    }
}

const pickerLocale = computed(() => (props.locale ? emojibaseLocale(props.locale) : cpEmojibaseLocale()));

const allowedCategories = computed(() =>
    props.categories?.length ? CATEGORIES.filter((key) => props.categories.includes(key)) : CATEGORIES,
);
const tab = ref("search");

const custom = useCustomEmojis();
const customList = computed(() => (props.customEmojis ? custom.list : []));
const asItem = (emoji) => ({ emoji: `:${emoji.shortcode}:`, label: emoji.label });

const recent = useRecentEmojis();
const recentItems = computed(() =>
    recent.value.filter((item) =>
        isShortcode(item.emoji) ? props.customEmojis && (!custom.loaded || findCustom(item.emoji)) : true,
    ),
);
const recentRow = computed(() =>
    props.recentEmojis && !props.categories?.length ? recentItems.value.slice(0, props.columns * 2) : [],
);

const hovered = ref(null);

const tabs = computed(() => [
    {
        key: "search",
        icon: Search,
        label: t("search_tab"),
    },
    ...allowedCategories.value.map((key) => ({
        key,
        icon: CATEGORY_ICONS[key],
        label: t(`categories.${key}`),
    })),
    ...(customList.value.length ? [{ key: "custom", icon: Sparkles, label: t("custom.tab") }] : []),
]);

// Kept outside frimousse, so the search survives switching tabs (the picker remounts per tab)
const search = ref("");

// Ones in "Recently used" are skipped: frimousse searches that category too
const customMatches = computed(() => {
    if (!props.customEmojis) return [];
    const inRecent = new Set(recentRow.value.map((item) => item.emoji));
    return searchCustom(search.value)
        .map(asItem)
        .filter((item) => !inRecent.has(item.emoji));
});

const gridItems = computed(() => (tab.value === "custom" ? customList.value.map(asItem) : null));

const dataUrl = computed(() =>
    emojibaseUrl(
        // The custom tab doesn't use frimousse; keep the search tab's data so its cache stays put
        ["search", "custom"].includes(tab.value) ? allowedCategories.value : [tab.value],
    ),
);

// Runs synchronously, so frimousse's cache is prepared before <EmojiPicker.Root> (re)mounts:
// the data of this URL, plus the "Recently used" category in the search tab
watch(
    [pickerLocale, dataUrl, tab],
    ([locale, url, currentTab]) => {
        prepareFrimousseCache(locale, url);
        applyRecentToFrimousseCache(locale, currentTab === "search" ? recentRow.value : [], t("recent_tab"));
    },
    { immediate: true, flush: "sync" },
);

// The popover focuses its content first, so wait a tick.
const pickerRoot = useTemplateRef("pickerRoot");

onMounted(() => {
    setTimeout(() => pickerRoot.value?.querySelector("[frimousse-search]")?.focus({ preventScroll: true }), 0);
});

function select(emoji, label) {
    hovered.value = null;
    rememberEmoji(emoji, label);
    emit("select", emoji);
}

function onEmojiSelect({ emoji, label }) {
    select(emoji, label);
}
</script>

<template>
    <div ref="pickerRoot" class="emojis-picker">
        <!-- The tab bar never widens the picker; if the tabs don't fit, it scrolls sideways -->
        <div class="emojis-picker__tabs" role="tablist">
            <component
                :is="Button"
                v-for="item in tabs"
                :key="item.key"
                :size="tabs.length > 9 ? 'xs' : 'sm'"
                :active="tab === item.key"
                :label="item.label"
                role="tab"
                :aria-selected="tab === item.key"
                @click="tab = item.key"
            >
                <component :is="item.icon" class="emojis-picker__tab-icon" />
            </component>
        </div>

        <div v-if="gridItems" class="emojis-picker__body">
            <div class="emojis-picker__scroll">
                <EmojiGrid
                    :items="gridItems"
                    :columns="columns"
                    @select="select($event.emoji, $event.label)"
                    @hover="hovered = $event"
                />
            </div>
            <PickerFooter
                :emoji="hovered?.emoji"
                :label="hovered?.label"
                :skin-tone-selector="skinToneSelector"
                :skin-tone="pickerSkinTone"
                @update:skin-tone="onSkinToneChange"
            />
        </div>

        <!-- :key remounts the picker when the locale, tab or data changes, as frimousse only reads them on creation -->
        <EmojiPicker.Root
            v-else
            :key="`${pickerLocale}|${dataUrl}`"
            class="emojis-picker__body"
            :locale="pickerLocale"
            :columns="columns"
            :skin-tone="pickerSkinTone"
            :emojibase-url="dataUrl"
            :on-emoji-select="onEmojiSelect"
        >
            <EmojiPicker.Search
                v-if="tab === 'search'"
                class="emojis-picker__search"
                :placeholder="t('search')"
                :value="search"
                @input="search = $event.target.value"
            />

            <div v-if="tab === 'search' && customMatches.length" class="emojis-picker__custom-matches">
                <div class="emojis-picker__header">{{ t("custom.tab") }}</div>
                <EmojiGrid
                    :items="customMatches"
                    :columns="columns"
                    @select="select($event.emoji, $event.label)"
                    @hover="hovered = $event"
                />
            </div>

            <EmojiPicker.Viewport class="emojis-picker__viewport">
                <EmojiPicker.Loading class="emojis-picker__status">{{ t("loading") }}</EmojiPicker.Loading>
                <EmojiPicker.Empty class="emojis-picker__status">{{ t("no_results") }}</EmojiPicker.Empty>
                <!-- Only the cell's content is replaced; frimousse keeps click, hover and keyboard handling -->
                <EmojiPicker.List class="emojis-picker__list">
                    <template #emoji="{ emoji }">
                        <EmojiImage :emoji="emoji.emoji" />
                    </template>
                </EmojiPicker.List>
            </EmojiPicker.Viewport>

            <EmojiPicker.ActiveEmoji v-slot="{ emoji }">
                <PickerFooter
                    :emoji="hovered ? hovered.emoji : emoji?.emoji"
                    :label="hovered ? hovered.label : emoji?.label"
                    :skin-tone-selector="skinToneSelector"
                    :skin-tone="pickerSkinTone"
                    @update:skin-tone="onSkinToneChange"
                />
            </EmojiPicker.ActiveEmoji>
        </EmojiPicker.Root>
    </div>
</template>
