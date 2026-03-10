import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');

    const appUrl = env.APP_URL || 'http://localhost:8080';
    const parsedAppUrl = new URL(appUrl);

    const serverPort = Number(env.VITE_PORT || 5173);
    const serverHost = env.VITE_HOST || '0.0.0.0';
    const hmrHost = env.VITE_HMR_HOST || parsedAppUrl.hostname || 'localhost';
    const hmrProtocol = env.VITE_HMR_PROTOCOL || 'ws';
    const appPort = Number(parsedAppUrl.port || 8080);
    const allowedOrigins = [
        `http://${hmrHost}:${appPort}`,
        `http://127.0.0.1:${appPort}`,
        `http://localhost:${appPort}`,
    ];

    return {
        plugins: [
            laravel({
                input: [
                    'resources/sass/app.scss',
                    'resources/js/app.js',
                ],
                refresh: true,
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
        ],
        resolve: {
            alias: {
                vue: 'vue/dist/vue.esm-bundler.js',
            },
        },
        server: {
            host: serverHost,
            port: serverPort,
            strictPort: true,
            origin: `http://${hmrHost}:${serverPort}`,
            cors: {
                origin: (origin) => {
                    if (!origin) return true;
                    return allowedOrigins.includes(origin);
                },
            },
            hmr: {
                host: hmrHost,
                protocol: hmrProtocol,
                clientPort: serverPort,
            },
        },
        // Configuração para silenciar os avisos do Bootstrap/Sass
        css: {
            preprocessorOptions: {
                scss: {
                    api: 'modern-compiler',
                    quietDeps: true,
                    silenceDeprecations: ['import', 'global-builtin', 'color-functions', 'if-function'],
                },
            },
        },
    };
});