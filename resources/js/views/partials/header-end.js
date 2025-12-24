import {createApp} from "vue";

createApp({
    name: 'HeaderEnd',
    data() {
        return {
            theme: window.theme,
            themeType: window.themeType,
        }
    },
    methods: {
        toggleThemeMode: async function () {
            if (this.theme === this.themeType.light) {
                this.theme = this.themeType.dark;
            } else {
                this.theme = this.themeType.light;
            }

            document.documentElement.setAttribute('data-bs-theme', this.theme);

            let data = {
                theme: this.theme,
            };

            await axios.post(route('update-theme'), data);
        },
    }
}).mount('#header-end')
