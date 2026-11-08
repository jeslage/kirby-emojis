import { defineConfig } from "vitest/config";
import vue from "@vitejs/plugin-vue";

export default defineConfig({
    plugins: [vue()],
    resolve: {
        // Templates of the test stubs are compiled at runtime
        alias: { vue: "vue/dist/vue.esm-bundler.js" },
    },
    test: {
        environment: "jsdom",
        include: ["tests/js/**/*.test.js"],
        setupFiles: ["tests/js/setup.js"],
    },
});
