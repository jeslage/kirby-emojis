<script>
// Panel → Emojis: site-wide picker settings, custom emojis and the image cache
import { t } from "../../shared/host.js";
import { refreshConfig } from "../host.js";
import CustomEmojisTab from "./CustomEmojisTab.vue";
import ImageCacheTab from "./ImageCacheTab.vue";

export default {
    components: { CustomEmojisTab, ImageCacheTab },
    props: {
        pro: Boolean,
        proUrl: String,
        canManage: Boolean,
        license: Object,
        fields: Object,
        values: Object,
        custom: Object,
        cache: Array,
    },
    data() {
        return {
            tab: "general",
            form: { ...this.values },
            saved: JSON.stringify(this.values),
            saving: false,
        };
    },
    computed: {
        tabs() {
            const tabs = [{ name: "general", label: t("settings.tab_general"), icon: "settings" }];

            if (this.pro) {
                tabs.push(
                    {
                        name: "custom",
                        label: t("settings.tab_custom"),
                        icon: "sparkling",
                        badge: this.custom.list.length || false,
                    },
                    { name: "cache", label: t("settings.tab_cache"), icon: "image" },
                );
            }

            return tabs.map((tab) => ({ ...tab, click: () => (this.tab = tab.name) }));
        },
        changed() {
            return JSON.stringify(this.form) !== this.saved;
        },
    },
    watch: {
        values(values) {
            this.form = { ...values };
            this.saved = JSON.stringify(values);
        },
    },
    mounted() {
        window.addEventListener("keydown", this.onKeydown);
    },
    beforeUnmount() {
        window.removeEventListener("keydown", this.onKeydown);
    },
    methods: {
        t,
        onKeydown(event) {
            if ((event.metaKey || event.ctrlKey) && event.key === "s" && this.tab === "general") {
                event.preventDefault();
                this.save();
            }
        },
        async save() {
            if (!this.canManage || this.saving) return;
            this.saving = true;

            try {
                const props = await this.$panel.api.post("emojis/settings", this.form);
                this.saved = JSON.stringify(props.values);
                this.form = { ...props.values };
                refreshConfig((await this.$panel.api.get("emojis/config")) ?? {});
                this.$panel.notification.success(t("settings.saved"));
                this.$panel.view.reload();
            } catch (error) {
                this.$panel.notification.error(error);
            } finally {
                this.saving = false;
            }
        },
        discard() {
            this.form = JSON.parse(this.saved);
        },
        activate() {
            this.$panel.dialog.open(this.license.dialog);
        },
    },
};
</script>

<template>
    <k-panel-inside class="k-emojis-view">
        <k-header>
            {{ t("custom.nav") }}
            <template #buttons>
                <k-button-group v-if="tab === 'general' && canManage">
                    <k-button
                        v-if="changed"
                        icon="undo"
                        size="sm"
                        variant="filled"
                        theme="notice"
                        :text="$t('discard')"
                        @click="discard"
                    />
                    <k-button
                        icon="check"
                        size="sm"
                        variant="filled"
                        :theme="changed ? 'notice' : 'passive'"
                        :disabled="!changed || saving"
                        :text="$t('save')"
                        @click="save"
                    />
                </k-button-group>
            </template>
        </k-header>

        <k-box v-if="!pro" theme="love" icon="key" class="k-emojis-pro-box">
            <div class="k-emojis-pro-text">
                <strong>{{ t("settings.pro_alert_heading") }}</strong>
                <p>{{ t("settings.pro_alert_text") }}</p>
            </div>
            <k-button-group>
                <k-button :link="proUrl" target="_blank" icon="cart" size="sm" variant="filled" theme="love">
                    {{ t("settings.get_pro") }}
                </k-button>
                <k-button icon="key" size="sm" variant="filled" @click="activate">
                    {{ t("license.activate") }}
                </k-button>
            </k-button-group>
        </k-box>

        <k-tabs :tab="tab" :tabs="tabs" />

        <k-form
            v-if="tab === 'general'"
            :fields="fields"
            :value="form"
            :disabled="!canManage"
            @input="form = { ...form, ...$event }"
            @submit="save"
        />
        <CustomEmojisTab v-else-if="tab === 'custom'" :custom="custom" :enabled="!!values.custom_emojis" />
        <ImageCacheTab v-else-if="tab === 'cache'" :sets="cache" />
    </k-panel-inside>
</template>

<style>
.k-emojis-view .k-tabs {
    margin-bottom: var(--spacing-8);
}
.k-emojis-pro-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--spacing-6);
    margin-bottom: var(--spacing-8);
}
.k-emojis-pro-text p {
    margin-top: var(--spacing-1);
}
</style>
