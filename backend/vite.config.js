import { defineConfig, loadEnv } from "vite";
import vue from "@vitejs/plugin-vue";
import vuetify from "vite-plugin-vuetify";
import path from "path";

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), "");

    const backendProxy =
        env.VITE_BACKEND_PROXY;

    return {
        plugins: [vue(), vuetify({ autoImport: true })],

        resolve: {
            alias: {
                "@": path.resolve(__dirname, "./src"),
            },
        },

        server: {
            host: "0.0.0.0",
            port: 5173,

            watch: {
                usePolling: true,
                interval: 1000,
            },
            /*
                        proxy: {
                            "/api": {
                                target: backendProxy,
                                changeOrigin: true,
                                secure: false,
                            },
                        },
                    }
            */
            preview: {
                host: "0.0.0.0",
                port: 4173,
            },
        };
    });