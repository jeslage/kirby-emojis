<script>
// Custom emojis (Pro): upload, preview, open or delete the files
import { t } from "../../shared/host.js";
import { reloadCustomEmojis } from "../../shared/custom-emojis.js";

export default {
    props: {
        custom: Object,
        enabled: Boolean,
    },
    methods: {
        t,
        upload() {
            this.$panel.upload.pick({
                url: this.$panel.urls.api + "/" + this.custom.uploadUrl,
                accept: "image/*",
                multiple: true,
                attributes: { template: this.custom.template },
                on: {
                    done: () => {
                        reloadCustomEmojis();
                        this.$panel.view.reload();
                    },
                },
            });
        },
        open(emoji) {
            this.$panel.view.open(this.fileLink(emoji));
        },
        fileLink(emoji) {
            const filename = emoji.id.split("/").pop();
            const parent = emoji.id.includes("/") ? emoji.id.split("/").slice(0, -1).join("+") : null;
            return parent ? `/pages/${parent}/files/${filename}` : `/site/files/${filename}`;
        },
    },
};
</script>

<template>
    <section class="k-emojis-custom">
        <k-section
            :headline="t('settings.custom_grid')"
            :buttons="[{ icon: 'upload', text: t('settings.custom_manage'), click: upload }]"
        >
            <k-box
                v-if="!enabled"
                theme="notice"
                icon="alert"
                :text="t('settings.custom_disabled')"
                class="k-emojis-custom-note"
            />

            <k-empty v-if="!custom.list.length" icon="sparkling" @click="upload">
                {{ t("settings.custom_empty") }} {{ t("settings.custom_setup") }}
            </k-empty>

            <ul v-else class="k-emojis-custom-grid">
                <li v-for="emoji in custom.list" :key="emoji.shortcode">
                    <button type="button" class="k-emojis-custom-item" :title="emoji.label" @click="open(emoji)">
                        <img :src="emoji.url" :alt="emoji.label" draggable="false" />
                        <code>:{{ emoji.shortcode }}:</code>
                    </button>
                </li>
            </ul>
        </k-section>
    </section>
</template>

<style>
.k-emojis-custom-note {
    margin-bottom: var(--spacing-3);
}
.k-emojis-custom-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(8rem, 1fr));
    gap: var(--spacing-2);
}
.k-emojis-custom-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--spacing-2);
    width: 100%;
    padding: var(--spacing-3);
    background: var(--item-color-back);
    border-radius: var(--rounded);
    box-shadow: var(--shadow-sm);
    cursor: pointer;
}
.k-emojis-custom-item:hover {
    box-shadow: var(--shadow-md);
}
.k-emojis-custom-item img {
    width: 2.5rem;
    height: 2.5rem;
    object-fit: contain;
}
.k-emojis-custom-item code {
    max-width: 100%;
    overflow: hidden;
    font-size: var(--text-xs);
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
