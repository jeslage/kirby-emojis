import "./shared/emojis.css";
import "./kirby/tokens.css";
import { setupHost } from "./kirby/host.js";
import EmojiField from "./kirby/fields/EmojiField.vue";
import EmojiTextField from "./kirby/fields/EmojiTextField.vue";
import EmojiFieldPreview from "./kirby/fields/EmojiFieldPreview.vue";
import EmojisView from "./kirby/views/EmojisView.vue";
import emojiNode from "./kirby/writer/emoji-node.js";
import emojiButton from "./kirby/textarea/emoji-button.js";
import { installTextareaSuggestions } from "./kirby/textarea/suggestions.js";

setupHost();
installTextareaSuggestions();

window.panel.plugin("jeslage/emojis", {
    fields: {
        emoji: EmojiField,
        emojitext: EmojiTextField,
    },
    components: {
        "k-emoji-field-preview": EmojiFieldPreview,
        "k-emojitext-field-preview": EmojiFieldPreview,
        "k-emojis-view": EmojisView,
    },
    writerNodes: {
        emoji: emojiNode,
    },
    textareaButtons: {
        emoji: emojiButton,
    },
});
