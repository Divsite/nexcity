import { createApp } from "vue";
import { select2 } from "../directives/select2";

function mountLivewireSelects() {
    document.querySelectorAll('[data-livewire-select]').forEach((el) => {
        if (el.dataset.mounted === '1') {
            return;
        }

        const livewireId = el.dataset.livewireId;
        const modelField = el.dataset.modelField || 'value';
        const optionValueKey = el.dataset.optionValueKey || 'id';
        const optionLabelKey = el.dataset.optionLabelKey || 'name';
        const placeholder = el.dataset.placeholder || window.messages?.please_select || '';

        if (!livewireId || !window.Livewire) {
            return;
        }

        const options = el.dataset.options ? JSON.parse(el.dataset.options) : [];
        const selected = el.dataset.selected ?? '';

        const app = createApp({
            directives: { select2 },
            data() {
                return {
                    selected: selected || '',
                    options,
                    optionValueKey,
                    optionLabelKey,
                };
            },
            watch: {
                selected(value) {
                    window.Livewire.find(livewireId)?.set(modelField, value || null);
                },
            },
            template: `
                <select class="form-control select2" v-model="selected" :data-placeholder="placeholder" v-select2>
                    <option></option>
                    <option
                        v-for="item in options"
                        :key="item[optionValueKey]"
                        :value="item[optionValueKey]"
                    >
                        {{ item[optionLabelKey] }}
                    </option>
                </select>
            `,
            computed: {
                placeholder() {
                    return placeholder;
                },
            },
        });

        app.mount(el);

        const select = el.querySelector('select');
        if (select) {
            const initSelect2 = () => {
                if ($(select).data('select2')) {
                    $(select).select2('destroy');
                }

                const modalParent = $(select).closest('.modal');
                $(select).select2({
                    width: '100%',
                    dropdownParent: modalParent.length ? modalParent : undefined,
                    minimumResultsForSearch: 0,
                    language: {
                        noResults: function () {
                            return window.messages?.no_results_found || 'No results found';
                        },
                    },
                }).on('change', function () {
                    const value = $(this).val();
                    window.Livewire.find(livewireId)?.set(modelField, value || null);
                });
            };

            initSelect2();
            setTimeout(initSelect2, 200);
        }

        el.dataset.mounted = '1';
    });
}

if (window.Livewire) {
    window.Livewire.hook('message.processed', () => {
        mountLivewireSelects();
    });
}

document.addEventListener('livewire:initialized', () => {
    mountLivewireSelects();
});

window.addEventListener('select-sync', (event) => {
    const detail = event.detail || {};
    const livewireId = detail.id;
    const field = detail.field;
    const value = detail.value ?? '';

    if (!livewireId || !field) {
        return;
    }

    const wrapper = document.querySelector(
        `[data-livewire-select][data-livewire-id="${livewireId}"][data-model-field="${field}"]`
    );

    if (!wrapper) {
        return;
    }

    wrapper.dataset.selected = value;
    const select = wrapper.querySelector('select');
    if (select) {
        $(select).val(value).trigger('change');
    }
});

if (document.readyState === 'complete' || document.readyState === 'interactive') {
    mountLivewireSelects();
} else {
    document.addEventListener('DOMContentLoaded', mountLivewireSelects);
}
