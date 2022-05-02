import { defineConfig } from "vite";
import symfonyPlugin from "vite-plugin-symfony";

/* if you're using React */
// import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        /* react(), // if you're using React */
        symfonyPlugin(),
    ],
    root: ".",
    base: "/build/",
    build: {
        manifest: true,
        emptyOutDir: true,
        assetsDir: "",
        outDir: "./public/build",
        rollupOptions: {
            input: {
                'security.login' : "./assets/js/security/login.js",
                'job.index' : "./assets/js/job/index.js",
                'job.view' : "./assets/js/job/view.js",
                'job.add' : "./assets/js/job/add.js"
            },
        },
    },
    server: {
        hmr: {
            protocol: 'ws'
        }
    }
});
