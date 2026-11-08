<script>
// Image cache (Pro): status per set, fill it in chunks or clear it
import { t } from "../../shared/host.js";

const CHUNK = 50;

export default {
    props: {
        sets: { type: Array, default: () => [] },
    },
    data() {
        return {
            status: this.sets,
            busy: null,
            progress: 0,
            failed: {},
        };
    },
    watch: {
        sets(sets) {
            this.status = sets;
        },
    },
    methods: {
        t,
        size(bytes) {
            if (bytes < 1024) return `${bytes} B`;
            if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
            return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
        },
        async refresh() {
            this.status = await this.$panel.api.get("emojis/cache");
        },
        async warm(set) {
            this.busy = set.set;
            this.progress = 0;
            let offset = 0;
            let failed = 0;

            try {
                for (;;) {
                    const result = await this.$panel.api.post(`emojis/cache/${set.set}/warm`, { offset, limit: CHUNK });
                    failed += result.failed.length;
                    offset = result.next;
                    this.progress = Math.round((result.next / Math.max(result.total, 1)) * 100);
                    if (result.done) break;
                }
                this.failed = { ...this.failed, [set.set]: failed };
            } catch (error) {
                this.$panel.notification.error(error);
            } finally {
                this.busy = null;
                await this.refresh();
            }
        },
        async clear(set) {
            this.busy = set.set;

            try {
                await this.$panel.api.post(`emojis/cache/${set.set}/clear`);
                this.failed = { ...this.failed, [set.set]: undefined };
            } catch (error) {
                this.$panel.notification.error(error);
            } finally {
                this.busy = null;
                await this.refresh();
            }
        },
    },
};
</script>

<template>
    <k-section :headline="t('settings.cache')" class="k-emojis-cache">
        <p class="k-emojis-cache-help">{{ t("settings.cache_instructions") }}</p>

        <div class="k-emojis-cache-sets">
            <div v-for="set in status" :key="set.set" class="k-emojis-cache-set">
                <div class="k-emojis-cache-info">
                    <strong>{{ set.label }}</strong>
                    <span>
                        {{ t("settings.cache_cached", { cached: set.cached, total: set.total ?? "?" }) }}
                        · {{ t("settings.cache_size", { size: size(set.size) }) }}
                    </span>
                    <span v-if="failed[set.set]" class="k-emojis-cache-failed">
                        {{ t("settings.cache_failed", { count: failed[set.set] }) }}
                    </span>
                </div>

                <div v-if="busy === set.set && progress" class="k-emojis-cache-progress">
                    <k-progress :value="progress" />
                    <span>{{ t("settings.cache_warming", { percent: progress }) }}</span>
                </div>

                <k-button-group>
                    <k-button
                        icon="download"
                        size="sm"
                        variant="filled"
                        :disabled="busy !== null"
                        :text="t('settings.cache_warm')"
                        @click="warm(set)"
                    />
                    <k-button
                        icon="trash"
                        size="sm"
                        variant="filled"
                        theme="negative"
                        :disabled="busy !== null || !set.cached"
                        :text="t('settings.cache_clear')"
                        @click="clear(set)"
                    />
                </k-button-group>
            </div>
        </div>
    </k-section>
</template>

<style>
.k-emojis-cache-help {
    margin-bottom: var(--spacing-3);
    color: var(--color-text-dimmed);
}
.k-emojis-cache-sets {
    display: grid;
    gap: var(--spacing-2);
}
.k-emojis-cache-set {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--spacing-3) var(--spacing-6);
    padding: var(--spacing-3) var(--spacing-4);
    background: var(--item-color-back);
    border-radius: var(--rounded);
    box-shadow: var(--shadow-sm);
}
.k-emojis-cache-info {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: var(--spacing-1);
    min-width: 12rem;
}
.k-emojis-cache-info span {
    font-size: var(--text-sm);
    color: var(--color-text-dimmed);
}
.k-emojis-cache-failed {
    color: var(--color-orange-700) !important;
}
.k-emojis-cache-progress {
    display: flex;
    flex: 1;
    align-items: center;
    gap: var(--spacing-3);
    min-width: 12rem;
    font-size: var(--text-sm);
}
</style>
