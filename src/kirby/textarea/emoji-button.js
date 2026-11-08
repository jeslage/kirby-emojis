// Textarea toolbar button `emoji` (add it to the field's `buttons`)
import EmojiPickerPopover from "../components/EmojiPickerPopover.vue";
import { closePopover, isPopoverOpen, openPopover } from "../popover.js";
import { ensureConfig } from "../host.js";
import { t } from "../../shared/host.js";

export default {
    get label() {
        return t("insert_emoji");
    },
    icon: "smile",
    // `this` is the textarea toolbar
    click() {
        if (isPopoverOpen()) return closePopover();

        ensureConfig();
        const anchor = document.activeElement?.closest?.("button") ?? this.$el;

        openPopover(EmojiPickerPopover, {
            anchor,
            onSelect: (emoji) => {
                closePopover();
                this.command("insert", emoji);
            },
        });
    },
};
