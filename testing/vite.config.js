import { defineConfig } from "vite";
import tailwindcss from "@tailwindcss/vite";
import laravel from "laravel-vite-plugin";
import { wordpressPlugin, wordpressThemeJson } from "@roots/vite-plugin";
import fs from "fs";
import path from "path";

export default defineConfig({
  base: "/build/",
  plugins: [
    tailwindcss(),
    laravel({
      input: [
        "resources/css/app.css",
        "resources/js/app.js",
        "resources/css/editor.css",
        "resources/js/editor.js",
      ],
      refresh: true,
      assets: ['resources/images/**', 'resources/fonts/**'],
    }),

    wordpressPlugin(),

    wordpressThemeJson({
      disableTailwindColors: false,
      disableTailwindFonts: false,
      disableTailwindFontSizes: false,
      disableTailwindBorderRadius: false,
    }),
    {
      name: "merge-theme-json",
      async closeBundle() {
        const out = path.resolve("public/build/assets/theme.json");
        const dir = path.resolve("resources/views/blocks/");
        if (!fs.existsSync(out)) return;
        const theme = JSON.parse(fs.readFileSync(out, "utf8"));
        theme.styles ??= {};

        // Deep merge function
        function deepMerge(target, source) {
          for (const key in source) {
            if (source[key] && typeof source[key] === 'object' && !Array.isArray(source[key])) {
              if (!target[key] || typeof target[key] !== 'object') {
                target[key] = {};
              }

              deepMerge(target[key], source[key]);
              continue;
            }
            target[key] = source[key];
          }
          return target;
        }

        for (const f of fs.readdirSync(dir).filter(f => f.endsWith('.theme.js'))) {
          try {
            const blockTheme = (await import(path.join(dir, f))).default;

            // If the theme has a 'styles' property, merge it as-is
            if (blockTheme.styles) {
              deepMerge(theme, blockTheme);
            }

            // Merge blocks and elements into theme.styles
            if (blockTheme.blocks) {
              theme.styles.blocks = theme.styles.blocks || {};
              deepMerge(theme.styles.blocks, blockTheme.blocks);
            }

            if (blockTheme.elements) {
              theme.styles.elements = theme.styles.elements || {};
              deepMerge(theme.styles.elements, blockTheme.elements);
            }
          } catch {}
        }
        fs.writeFileSync(out, JSON.stringify(theme, null, 2));
      },
    },
  ],
  resolve: {
    alias: {
      "@scripts": "/resources/js",
      "@styles": "/resources/css",
      "@fonts": "/resources/fonts",
      "@images": "/resources/images",
    },
  },
});
