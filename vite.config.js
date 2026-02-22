import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import {viteStaticCopy} from "vite-plugin-static-copy";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/views/auth/login.js',
                'resources/js/views/auth/passwords/email.js',
                'resources/js/views/auth/passwords/reset.js',
                'resources/js/views/auth/register.js',
                'resources/js/views/partials/header-end.js',
                'resources/js/views/partials/delete-confirmation.js',
                'resources/js/views/profiles/index.js',
                'resources/js/views/profiles/change-password.js',
                'resources/js/views/profiles/change-username.js',
                'resources/js/views/profiles/change-email.js',
                'resources/js/views/roles/create.js',
                'resources/js/views/roles/edit.js',
                'resources/js/views/users/create.js',
                'resources/js/views/users/edit.js',
                'resources/js/views/forms/builder.js',
                'resources/js/views/forms/render.js',
                'resources/js/views/groups/create.js',
                'resources/js/views/groups/edit.js',
                'resources/js/views/form-types/create.js',
                'resources/js/views/form-types/edit.js',
                'resources/js/views/partials/read-confirmation.js',
                'resources/js/views/form-processes/index.js',
                'resources/js/views/submissions/show.js',
                'resources/js/views/settings/system.js',
                'resources/js/views/organizations/form.js',
                'resources/js/views/residents/form.js',
                'resources/js/views/roles/internal/index.js',
                'resources/js/views/settings/menus/form.js',
                'resources/js/views/settings/partner-users/create.js',
                'resources/js/views/settings/partner-users/edit.js',
                'resources/js/views/settings/user-management/index.js',
                'resources/js/livewire/currency.js',
                'resources/js/livewire/select.js',
                'resources/js/views/charities/form.js',
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
        viteStaticCopy({
            targets: [
                {
                    src: 'node_modules/select2/dist/css/select2.min.css',
                    dest: 'libs/select2/css/'
                }
            ]
        })
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
            'vue': 'vue/dist/vue.esm-bundler.js'
        },
    },
    // Hide console.log after run `npm run build`
    // Source: https://github.com/vitejs/vite/discussions/7920
    esbuild: {
        drop: ['console', 'debugger'],
    },
});
